<?php
declare(strict_types=1);

namespace app\service;

use app\exception\ApiException;
use app\model\Gifts;
use app\model\GiftType;
use app\model\Users;
use app\model\IntegralRecord;
use app\model\TgStarTransactions;
use think\facade\Db;
use think\facade\Log;
use \Godruoyi\Snowflake\Snowflake;
use \TelegramBot\Api\BotApi;

class LotteryService extends BaseService
{
    protected $giftService;
    protected $telegram;

    public function __construct()
    {
        parent::__construct();
        $this->giftService = new GiftService();
        $this->telegram = new BotApi(env('telegram.token'));
    }

    public function createTransaction(int $typeId)
    {

        // 获取档位信息
        $giftType = GiftType::where(["id" => $typeId, "show" => 1])->findOrEmpty()->toArray();
        if (empty($giftType)) {
            throw new ApiException('抽奖不存在');
        }

        //查询是否发放过免费抽奖积分
        $hasLottery = TgStarTransactions::where(["user_id" => $this->user_id])->findOrEmpty()->toArray();
        if (empty($hasLottery)) {
            [$transaction_id,$integral] = $this->doLotteryIntegral($giftType);
            $invoicelink = "";
        } else {
            $integral = 0;
            [$transaction_id,$invoicelink] = $this->createInvoiceLink($giftType);
        }
        return [
            'transaction_id' => $transaction_id,
            'integral' => $integral,
            'invoicelink' => $invoicelink
        ];
    }

    /**
     * 创建Telegram发票链接
     */
    public function createInvoiceLink($giftType)
    {

        $transaction_id = (new Snowflake)->id();
        $invoicelink = $this->telegram->call("createInvoiceLink", data: [
            'title' => $giftType['type_name'],
            'description' => $giftType['description'],
            'payload' => $transaction_id,
            'currency' => "XTR",
            'prices' => json_encode([
                [
                    'label' => 'price',
                    'amount' => $giftType['pay_star'],
                ]
            ])
        ], timeout: 10);

        $transaction = new TgStarTransactions();
        $transaction->user_id = $this->user_id;
        $transaction->user_tg_id = $this->telegram_id;
        $transaction->gift_type = $giftType['id'];
        $transaction->transaction_id = $transaction_id;
        $transaction->pay_status = 0;
        $transaction->transaction_star_amount = $giftType['pay_star'];
        $transaction->award_status = 0;
        $transaction->award_type = 2;
        $transaction->award_time = time();
        $transaction->save();
        return [$transaction->transaction_id,$invoicelink];
    }

    /**
     * 执行抽奖
     * @param array $typeId 档位ID
     * @return mixed
     * @throws \Exception
     */
    public function doLotteryIntegral($giftType)
    {
        // 随机积分 10-100
        $integral = mt_rand(10, 100);

        // 记录抽奖结果
        Db::startTrans();
        try {
            // 记录积分变动
            $integralRecord = new IntegralRecord();
            $integralRecord->user_id = $this->user_id;
            $integralRecord->change_num = $integral;
            $integralRecord->type = 1; // 1-首次免费抽奖获得
            $integralRecord->save();

            $transaction = new TgStarTransactions();
            $transaction->user_id = $this->user_id;
            $transaction->user_tg_id = $this->telegram_id;
            $transaction->gift_type = $giftType['id'];
            $transaction->transaction_id = (new Snowflake)->id();
            $transaction->pay_status = 1;
            $transaction->transaction_star_amount = $giftType['pay_star'];
            $transaction->pay_time = time();
            $transaction->award_status = 1;
            $transaction->award_type = 1;
            $transaction->award_time = time();
            $transaction->save();

            // 更新用户积分
            Users::where('id', $this->user_id)->inc('integral_num', $integral)->update([]);

            Db::commit();
        } catch (\Exception $e) {
            Db::rollback();
            Log::error('积分抽奖失败：' . $e->getMessage());
            throw new ApiException('抽奖失败，请稍后再试');
        }
        return [$transaction->transaction_id,$integral];
    }

    /**
     * 执行抽奖
     * @param array $transaction_info 档位ID
     * @return array|\think\Response
     */
    public function doLotteryGift(array $transaction_info)
    {
        $typeId = $transaction_info["gift_type"];

        // 获取该档位下的所有奖品
        $gifts = $this->giftService->getGiftsByType($typeId);
        if (empty($gifts)) {
            throw new ApiException('奖品列表为空');
        }

        // 执行抽奖逻辑
        $award = $this->lottery($gifts);

        // 记录抽奖结果
        Db::startTrans();
        try {
            // 更新奖品中奖次数
            Gifts::where('id', $award['id'])->inc('occurrence_num', 1)->update([]);
            $this->sendGiftToUser($transaction_info['user_tg_id'],$award['gift_tg_id']);
            // 更新抽奖记录
            TgStarTransactions::where('transaction_id', $transaction_info['transaction_id'])
                ->update([
                    'gift_id' => $award['id'],
                    'gift_tg_id' => $award['gift_tg_id'],
                    'award_star' => $award['star_price'],
                    'award_status' => 1,
                    'award_type' => 2,
                    'award_time' => time(),
                    'gift_is_limit' => $award['is_limit'],
                ]);
            Db::commit();
            return ['code' => 1, 'data' => $award, 'is_free' => $award];
        } catch (\Exception $e) {
            Db::rollback();
            TgStarTransactions::where('transaction_id', $transaction_info['transaction_id'])
            ->update([
                'gift_id' => $award['id'],
                'gift_tg_id' => $award['gift_tg_id'],
                'award_star' => $award['star_price'],
                'award_status' => -1,
                'award_type' => 2,
                'award_time' => time(),
                'gift_is_limit' => $award['is_limit'],
                'award_error_remark' => $e->getMessage(),
            ]);
            Log::error('抽奖失败：' . $e->getMessage());
            throw new ApiException('抽奖失败' . $e->getMessage());
        }
    }

    /**
     * 抽奖算法
     * @param array $gifts 奖品列表
     * @return array 中奖奖品
     */
    protected function lottery(array $gifts)
    {
        // 计算总概率
        $totalProbability = 0;
        foreach ($gifts as $gift) {
            $totalProbability += $gift['probability'];
        }

        // 生成随机数
        $randomNum = mt_rand(1, 10000) / 10000 * $totalProbability;

        // 根据概率选择奖品
        $currentProbability = 0;
        foreach ($gifts as $gift) {
            $currentProbability += $gift['probability'];
            if ($randomNum <= $currentProbability) {
                return $gift;
            }
        }

        // 默认返回第一个奖品（理论上不会执行到这里）
        return $gifts[0];
    }

    /**
     * 发送礼物给用户
     * @param int $userId 用户ID
     * @param string $giftId 礼物ID
     * @return bool
     */
    public function sendGiftToUser($userTgId, $giftTgId)
    {
        // 调用Telegram Bot API的sendGift方法
        $this->telegram->call("sendGift", [
            'user_id' => $userTgId, // 用户的Telegram ID
            'gift_id' => $giftTgId, // 指定的礼物ID
        ]);
        return true;
    }
}