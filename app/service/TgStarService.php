<?php
declare (strict_types = 1);

namespace app\service;

use app\exception\ApiException;
use app\model\TgStarTransactions;
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

        if ($transaction_info["pay_status"] == 0 && 
            $preCheckoutQuery["currency"] == "XTR" && 
            $transaction_info['transaction_star_amount'] == $preCheckoutQuery['total_amount']
            ) {
            Log::debug("订单校验成功");
            $this->telegram->answerPreCheckoutQuery($preCheckoutQuery['id'], true);
            Log::debug("answer成功");
            return true;
        } else {
            Log::debug("订单校验失败");
            if ($transaction_info["pay_status"]!=-1 && $transaction_info["pay_status"]!=1){
                //记录日志
                Log::error('【Invalid transaction - Not Match1】: '. json_encode($preCheckoutQuery));
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

        if ($transaction_info["pay_status"] == 0 && 
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
                    (new LotteryService())->doLotteryGift($transaction_info);
                } catch (\Exception $e) {
                    // 处理抽奖失败的情况，例如记录错误日志
                    Log::error('【Invalid transaction - doLotteryGift Error】: '. $e->getMessage() . json_encode($successfulPayment));
                    return false;
                }
            return true;
        }else{
            //记录日志
            Log::error('【Invalid transaction - Not Match】: '. json_encode($successfulPayment));
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
        if (!isset($headers['x-telegram-bot-api-secret-token']) || 
            $headers['x-telegram-bot-api-secret-token'] !== env('telegram.secret_token')) {
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
        $record = TgStarTransactions::where(['transaction_id'=>$transaction_id,"user_id"=>$this->user_id])
            ->with(['gifts'])
            ->field("transaction_id,pay_status,pay_star_amount,pay_time,gift_id,gift_tg_id,gift_is_limit,award_star,award_status,award_time,award_error_remark")
            ->findOrEmpty()
            ->toArray();
        if (empty($record)) {
            throw new ApiException("Not found");
        }
        // 如果存在gift_tg_id，获取对应的动画JSON文件内容
        if (!empty($record) && !empty($record["gift_tg_id"]) && !empty($record["gifts"])) {
            $json_file_path = public_path() . 'static/' . $record["gift_tg_id"] . '.json';
            if (file_exists($json_file_path)) {
                $record["gifts"]["gift_animation"] = file_get_contents($json_file_path);
            } else {
                $record["gifts"]["gift_animation"] = "";
                Log::error('【Gift animation JSON file not found】: ' . $json_file_path);
            }
        }
        
        return $record;
    }

    /**
     * 获取用户的奖品信息
     * @param bool $is_limit 是否只获取限量奖品
     * @return array 用户的奖品信息
     */
    public function getUserGifts($is_limit = false)
    {
        $where = [
            "user_id"=>$this->user_id,
            "award_status"=>1,
            'award_type' => 2,
            "pay_status"=>1
        ];
        if ($is_limit) {
            $where["gift_is_limit"] = 1;
        }
        $user_gifts_model = $this->model->where($where)
            ->with(['gifts'])
            ->order('award_time','desc')
            ->field('id,award_star,gift_id');
        $list = $this->pageQuery($user_gifts_model);
        return $list;
    }
    
}