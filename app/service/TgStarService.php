<?php
declare(strict_types=1);

namespace app\service;

use app\exception\ApiException;
use app\model\TgStarIntegral;
use app\model\TgStarTransactions;
use app\model\Users;
use Godruoyi\Snowflake\Snowflake;
use think\facade\Log;
use \TelegramBot\Api\BotApi;

class TgStarService extends BaseService
{
    protected $telegram;
    protected $model;

    public function __construct()
    {
        parent::__construct();
        $this->telegram = new BotApi(env('telegram.token'));
        $this->model = new TgStarTransactions();
    }

    /**
     * 开始购买积分
     * @param mixed $type_id
     * @throws \app\exception\ApiException
     * @return array{integral_amount: mixed, invoicelink: mixed, star_amount: mixed, transaction_id: mixed}
     */
    public function doBuyIntegral($type_id)
    {
        // 获取档位信息
        $priceInfo = TgStarIntegral::where(["id" => $type_id, "is_show" => 1])->findOrEmpty()->toArray();
        if (empty($priceInfo)) {
            throw new ApiException('Not found');
        }
        // 创建Telegram发票链接
        [$transaction_id, $invoicelink] = $this->createInvoiceLink($priceInfo);
        return [
            "transaction_id" => $transaction_id,
            "invoicelink" => $invoicelink,
            "star_amount" => $priceInfo['tg_star_amount'],
            "integral_amount" => $priceInfo['integral_amount']
        ];
    }

    /**
     * 创建Telegram发票链接
     * @param array $giftType 档位信息
     * @return array
     */
    public function createInvoiceLink($priceInfo)
    {
        $transaction_id = (new Snowflake())->id();
        $invoicelink = $this->telegram->call("createInvoiceLink", data: [
            'title' => "Star",
            'description' => "Star change",
            'payload' => $transaction_id,
            'currency' => "XTR",
            'prices' => json_encode([
                [
                    'label' => 'price',
                    'amount' => $priceInfo['tg_star_amount'],
                ]
            ])
        ], timeout: 10);

        $transaction = new TgStarTransactions();
        $transaction->user_id = $this->user_id;
        $transaction->user_tg_id = $this->telegram_id;
        $transaction->transaction_id = $transaction_id;
        $transaction->transaction_star_amount = $priceInfo['tg_star_amount'];
        $transaction->transaction_integral_amount = $priceInfo['integral_amount'];
        $transaction->pay_status = 0;
        $transaction->save();
        return [$transaction->transaction_id,$invoicelink];
    }

    
    /**
     * 处理预检查请求
     * @param array $preCheckoutQuery 预检查查询数据
     * @return bool
     */
    public function handlePreCheckout(array $preCheckoutQuery)
    {
        $transaction_id = $preCheckoutQuery['invoice_payload'];
        $transaction_info = $this->model->where('transaction_id', $transaction_id)->findOrEmpty()->toArray();
        if (empty($transaction_info)) {
            Log::debug("订单不存在");
            $this->telegram->answerPreCheckoutQuery($preCheckoutQuery['id'], false, 'Invalid transaction');
            return false;
        }

        if (
            $transaction_info["pay_status"] == 0 &&
            $preCheckoutQuery["currency"] == "XTR" &&
            $transaction_info['transaction_star_amount'] == $preCheckoutQuery['total_amount']
        ) {
            Log::debug("订单校验成功");
            $this->telegram->answerPreCheckoutQuery($preCheckoutQuery['id'], true);
            Log::debug("answer成功");
            return true;
        } else {
            Log::debug("订单校验失败");
            if ($transaction_info["pay_status"] != -1 && $transaction_info["pay_status"] != 1) {
                //记录日志
                Log::error('【Invalid transaction - Not Match1】: ' . json_encode($preCheckoutQuery));
                TgStarTransactions::where('transaction_id', $transaction_id)
                    ->update(['pay_status' => -1]);
            }
            $this->telegram->answerPreCheckoutQuery($preCheckoutQuery['id'], false, 'Invalid transaction');
            Log::debug("answer成功");
            return false;
        }
    }

    /**
     * 处理成功支付请求
     * @param array $successfulPayment 成功支付数据
     * @return bool
     */
    public function handleSuccessfulPayment(array $successfulPayment)
    {
        $transaction_id = $successfulPayment['invoice_payload'];
        $transaction_info = TgStarTransactions::where('transaction_id', $transaction_id)->findOrEmpty()->toArray();
        if (empty($transaction_info)) {
            //记录日志
            Log::error('【Invalid transaction - Not Found】: ' . json_encode($successfulPayment));
            return false;
        }

        if (
            $transaction_info["pay_status"] == 0 &&
            $successfulPayment["currency"] == "XTR" &&
            $transaction_info['transaction_star_amount'] == $successfulPayment['total_amount']
        ) {
            //更新状态
            TgStarTransactions::where('transaction_id', $transaction_id)
                ->update([
                    'pay_status' => 1,
                    "tg_payment_charge_id" => $successfulPayment['telegram_payment_charge_id'] ?? "",
                    "provider_payment_charge_id" => $successfulPayment['provider_payment_charge_id'] ?? "",
                    "pay_star_amount" => $successfulPayment['total_amount'] ?? 0,
                    "pay_time" => $successfulPayment['date'] ?? time()
                ]);
            // 抽奖 && 发放奖品
            try {
                (new LotteryService())->addIntergralRecord($transaction_info);
            } catch (\Exception $e) {
                // 处理抽奖失败的情况，例如记录错误日志
                Log::error('【Invalid transaction - doLotteryGift Error】: ' . $e->getMessage() . json_encode($successfulPayment));
                return false;
            }
            return true;
        } else {
            //记录日志
            Log::error('【Invalid transaction - Not Match】: ' . json_encode($successfulPayment));
            TgStarTransactions::where('transaction_id', $transaction_id)
                ->update([
                    'pay_status' => -1,
                    "tg_payment_charge_id" => $successfulPayment['telegram_payment_charge_id'] ?? "",
                    "provider_payment_charge_id" => $successfulPayment['provider_payment_charge_id'] ?? "",
                    "pay_star_amount" => $successfulPayment['total_amount'] ?? 0,
                    "pay_time" => $successfulPayment['date'] ?? 0,
                ]);
            return false;
        }
    }

    /**
     * 验证Telegram回调请求
     * @param array $headers 请求头
     * @return bool
     */
    public function validateTelegramCallback(array $headers)
    {
        if (
            !isset($headers['x-telegram-bot-api-secret-token']) ||
            $headers['x-telegram-bot-api-secret-token'] !== env('telegram.secret_token')
        ) {
            return false;
        }

        return true;
    }

    /**
     * 处理Telegram回调请求
     * @param string|int $transaction_id 请求数据
     * @return mixed
     */
    public function getRecordByTransactionId($transaction_id)
    {
        $transaction_info = TgStarTransactions::where(['transaction_id' => $transaction_id, "user_id" => $this->user_id])
            ->field("transaction_id,pay_status,pay_star_amount,pay_time,transaction_star_amount,transaction_integral_amount")
            ->findOrEmpty()
            ->toArray();
        if (empty($transaction_info)) {
            throw new ApiException("Not found");
        }
        $user_integral_balance = Users::where('id', $this->user_id)->value('integral_num');

        return [
            "transaction_info" => $transaction_info,
            "user_integral_balance" => $user_integral_balance
        ];
    }

}