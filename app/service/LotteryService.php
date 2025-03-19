<?php
declare(strict_types=1);

namespace app\service;

use app\dict\CommonDict;
use app\exception\ApiException;
use app\model\Gifts;
use app\model\LotteryOrder;
use app\model\LotteryType;
use app\model\TgStarIntegral;
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
        $giftType = LotteryType::where(["id" => $typeId, "show" => 1])->findOrEmpty()->toArray();
        if (empty($giftType)) {
            throw new ApiException('Lottery type not found');
        }

        //查询是否发放过免费抽奖积分
        $hasLottery = LotteryOrder::where(["user_id" => $this->user_id])->findOrEmpty()->toArray();
        if (empty($hasLottery)) {
            $integral = $this->doLotteryIntegralFree($giftType);
            return [
                'raward_type' => 1, //积分
                'integral' => $integral
            ];
        }
        // 执行付费抽奖逻辑
        $award = $this->doLotteryIntegral($giftType);
        if(isset($award["transaction_id"]) && isset($award["invoicelink"])){
            return $award;
        }
        $award["gift_animation"] = getGiftAnimationString($award['gift_tg_id']);
        return [
            'raward_type' => 2, //礼物
            "gifts" => $award,
        ];
    }
    /**
     * 执行抽奖
     * @param array $typeId 档位ID
     * @return mixed
     * @throws \Exception
     */
    public function doLotteryIntegralFree($giftType)
    {
        // 随机积分 5 - 10
        $integral = mt_rand(5, 10);

        // 记录抽奖结果
        Db::startTrans();
        try {
            $transaction_id = (new Snowflake())->id();

            // 记录积分变动
            $integralRecord = new IntegralRecord();
            $integralRecord->user_id = $this->user_id;
            $integralRecord->user_tg_id = $this->telegram_id;
            $integralRecord->change_num = $integral;
            $integralRecord->transaction_id = $transaction_id;
            $integralRecord->type = CommonDict::INTEGRAL_TYPE_FREE; // 1-首次免费抽奖获得
            $integralRecord->save();

            $lottery_order_model = new LotteryOrder();
            $lottery_order_model->user_id = $this->user_id;
            $lottery_order_model->user_tg_id = $this->telegram_id;
            $lottery_order_model->pay_integral = 0;
            $lottery_order_model->order_uuid = $transaction_id;
            $lottery_order_model->lottery_type_id = $giftType['id'];
            $lottery_order_model->gift_id = 0;
            $lottery_order_model->save();

            // 更新用户积分
            Users::where('id', $this->user_id)->inc('integral_num', $integral)->update([]);

            Db::commit();
        } catch (\Exception $e) {
            Db::rollback();
            Log::error('免费积分抽奖失败：' . $e->getMessage());
            throw new ApiException('Lottery failed');
        }
        return $integral;
    }

    /**
     * 执行抽奖
     * @param array $giftType 档位信息
     * @return mixed
     * @throws \Exception
     */
    public function doLotteryIntegral($latteryType)
    {
        $user_integral = Users::where('id', $this->user_id)->value('integral_num');
        if ($user_integral < $latteryType['pay_integral']) {
            [$transaction_id, $invoicelink] = (new TgStarService())->createInvoiceLink([
                'tg_star_amount' => $latteryType['pay_integral'],
                'integral_amount' => $latteryType['pay_integral']
            ]);
            return [
                "user_integral" => $user_integral,
                "transaction_id" => $transaction_id,
                "invoicelink" => $invoicelink,
                "star_amount" => $latteryType['pay_integral'],
                "integral_amount" => $latteryType['pay_integral']
            ];
        }

        // 记录抽奖结果
        Db::startTrans();
        try {

            // 更新用户积分
            Users::where('id', $this->user_id)->dec('integral_num', $latteryType['pay_integral'])->update([]);

            $transaction_id = (new Snowflake())->id();
            // 记录积分变动
            $integralRecord = new IntegralRecord();
            $integralRecord->user_id = $this->user_id;
            $integralRecord->user_tg_id = $this->telegram_id;
            $integralRecord->change_num = -$latteryType['pay_integral'];
            $integralRecord->transaction_id = $transaction_id;
            $integralRecord->type = CommonDict::INTEGRAL_TYPE_LOTTERY;
            $integralRecord->save();

            // 获取该档位下的所有奖品
            $gifts = $this->giftService->getGiftsByType($latteryType['id']);
            if (empty($gifts)) {
                throw new ApiException('Gift not found');
            }

            // 执行抽奖逻辑
            $award = $this->lottery($gifts);

            $lottery_order_model = new LotteryOrder();
            $lottery_order_model->user_id = $this->user_id;
            $lottery_order_model->user_tg_id = $this->telegram_id;
            $lottery_order_model->pay_integral = $latteryType['pay_integral'];
            $lottery_order_model->order_uuid = $transaction_id;
            $lottery_order_model->lottery_type_id = $latteryType['id'];
            $lottery_order_model->gift_id = $award['id'];
            $lottery_order_model->gift_tg_id = $award['gift_tg_id'];
            $lottery_order_model->gift_is_limit = $award['is_limit'];
            $lottery_order_model->award_star = $award['star_price'];
            $lottery_order_model->save();

            Db::commit();
        } catch (\Exception $e) {
            Db::rollback();
            Log::error('【积分抽奖失败】：' . $e->getMessage());
            throw new ApiException('Lottery failed');
        }
        return $award;
    }


    public function addIntergralRecord($transaction_info)
    {
        // 记录积分变动
        $integralRecord = new IntegralRecord();
        $integralRecord->user_id = $transaction_info['user_id'];
        $integralRecord->user_tg_id = $transaction_info['user_tg_id'];
        $integralRecord->change_num = $transaction_info['transaction_integral_amount'];
        $integralRecord->transaction_id = $transaction_info['transaction_id'];
        $integralRecord->type = CommonDict::INTEGRAL_TYPE_TRANSACTION;
        $integralRecord->save();
        // 更新用户积分
        Users::where('id', $transaction_info['user_id'])->inc('integral_num', $transaction_info['transaction_integral_amount'])->update([]);
    }



    /**
     * 执行抽奖
     * @param array $transaction_info 档位ID
     * @return array|\think\Response
     */
    public function doLotteryGift(array $transaction_info)
    {
        $typeId = $transaction_info["lottery_type_id"];

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
            $this->sendGiftToUser($transaction_info['user_tg_id'], $award['gift_tg_id']);
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
     * 获取用户的奖品信息
     * @param bool $is_limit 是否只获取限量奖品
     * @return array 用户的奖品信息
     */
    public function getUserGifts($is_limit = false)
    {
        $where = [["award_status", "=", 0], ["user_id", "=", $this->user_id], ["gift_id", "<>", 0]];
        if ($is_limit) {
            array_push($where, ["gift_is_limit", "=", 1]);
        }
        $user_gifts_model = LotteryOrder::where($where)
            ->with(['gifts'])
            ->order('create_time', 'desc')
            ->field('id,user_id,user_tg_id,gift_id,gift_tg_id,award_star');
        $list = $this->pageQuery($user_gifts_model);
        return $list;
    }

    public function getUserAllGifts()
    {
        $user_limit_gifts = $this->getUserGifts(is_limit: true);
        $user_all_gifts = $this->getUserGifts(is_limit: false);

        return [
            "limit_gifts" => $user_limit_gifts,
            "all_gifts" => $user_all_gifts
        ];
    }

    /**
     * 购买积分列表
     * @return array 所有的兑换关系列表
     */
    public function starToIntegrayList()
    {
        $star_to_integray_list = TgStarIntegral::where(["is_show" => 1])
            ->order("tg_star_amount", "asc")
            ->field("id,tg_star_amount,integral_amount")
            ->select()
            ->toArray();
        return $star_to_integray_list;
    }

    public function giftToGift($id)
    {
        $order_info = LotteryOrder::where(["user_id" => $this->user_id, "id" => $id, 'award_status' => 0])
            ->append(['gift_animation'])
            ->findOrEmpty()
            ->toArray();
        if (empty($order_info)) {
            throw new ApiException('Not found');
        }
        try {
            $this->sendGiftToUser($order_info['user_tg_id'], $order_info['gift_tg_id']);
            LotteryOrder::where(["user_id" => $this->user_id, "gift_tg_id" => $order_info['gift_tg_id'], 'award_status' => 0])
                ->update(['award_status' => CommonDict::AWARD_STATUS_TO_GIFT, 'award_time' => time()]);
        } catch (\Exception $e) {
            LotteryOrder::where(["user_id" => $this->user_id, "gift_tg_id" => $order_info['gift_tg_id'], 'award_status' => 0])
                ->update(['award_error_remark' => $e->getMessage()]);
            Log::error('【礼物兑换失败】：' . $e->getMessage());
            throw new ApiException('Gift exchange failed');
        }
        return [
            "gift_animation" => $order_info['gift_animation'],
            "gift_tg_id" => $order_info['gift_tg_id']
        ];
    }

    public function giftToIntegral($id)
    {
        $order_info = LotteryOrder::where(["user_id" => $this->user_id, "id" => $id, 'award_status' => 0])
            ->findOrEmpty()
            ->toArray();
        if (empty($order_info)) {
            throw new ApiException('Not found');
        }
        LotteryOrder::where(["user_id" => $this->user_id, "gift_tg_id" => $order_info['gift_tg_id'], 'award_status' => 0])
            ->update(['award_status' => CommonDict::AWARD_STATUS_TO_INTEGRAL, 'award_time' => time()]);
        // 记录积分变动
        $integralRecord = new IntegralRecord();
        $integralRecord->user_id = $order_info['user_id'];
        $integralRecord->user_tg_id = $order_info['user_tg_id'];
        $integralRecord->change_num = $order_info['award_star'];
        $integralRecord->transaction_id = $order_info['order_uuid'];
        $integralRecord->type = CommonDict::INTEGRAL_TYPE_LOTTERY;
        $integralRecord->save();
        // 更新用户积分
        Users::where('id', $order_info['user_id'])->inc('integral_num', $order_info['award_star'])->update([]);
        return [
            "integral_num" => $order_info['award_star'],
            "gift_tg_id" => $order_info['gift_tg_id']
        ];
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