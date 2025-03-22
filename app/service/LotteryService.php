<?php
declare(strict_types=1);

namespace app\service;

use app\dict\commonDict;
use app\dict\lotteryDict;
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
use think\facade\Cache;
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
        $giftType = $this->getCache('lottery:type_' . $typeId, function () use ($typeId) {
            return LotteryType::where(["id" => $typeId, "show" => 1])->findOrEmpty()->toArray();
        }, 3600, 'lottery_types');

        if (empty($giftType)) {
            throw new ApiException('Lottery type not found');
        }

        //查询是否发放过免费抽奖积分
        $hasLottery = LotteryOrder::where(["user_id" => $this->user_id])->findOrEmpty()->toArray();
        if (empty($hasLottery)) {
            $integral = $this->doLotteryIntegralFree($giftType);
            return [
                'award_type' => lotteryDict::AWARD_TYPE_INTEGRAL, //积分
                'integral_price' => $integral
            ];
        }
        // 执行付费抽奖逻辑
        $award = $this->doLotteryIntegral($giftType);
        if (isset($award["transaction_id"]) && isset($award["invoicelink"])) {
            return $award;
        }
        if ($award["award_type"] == lotteryDict::AWARD_TYPE_GIFT) {
            $award["gift_animation"] = getGiftAnimationString($award['gift_tg_id']);
        }
        return $award;
    }
    /**
     * 执行抽奖
     * @param array $typeId 档位ID
     * @return mixed
     * @throws \Exception
     */
    public function doLotteryIntegralFree($giftType)
    {
        // 获取Redis锁，防止重复获取免费积分
        $lockKey = 'free_lottery:' . $this->user_id;
        if (!$this->getLock($lockKey, 30)) { // 30秒锁过期时间
            throw new ApiException('System busy, please try again later');
        }

        // 随机积分 5 - 10
        $integral = mt_rand(5, 10);

        // 记录抽奖结果
        Db::startTrans();
        try {
            // 再次检查是否已经抽过奖
            $hasLottery = LotteryOrder::where(["user_id" => $this->user_id])->findOrEmpty()->toArray();
            if (!empty($hasLottery)) {

                $this->releaseLock($lockKey);
                throw new ApiException('You have already participated in the lottery');
            }

            $transaction_id = (new Snowflake())->id();

            Users::where('id', $this->user_id)->update(["first_lottery" => 0]);

            $lottery_order_model = new LotteryOrder();
            $lottery_order_model->user_id = $this->user_id;
            $lottery_order_model->user_tg_id = $this->telegram_id;
            $lottery_order_model->pay_integral = 0;
            $lottery_order_model->order_uuid = $transaction_id;
            $lottery_order_model->lottery_type_id = $giftType['id'];
            $lottery_order_model->award_type = lotteryDict::AWARD_TYPE_INTEGRAL;
            $lottery_order_model->award_integral = $integral;
            $lottery_order_model->gift_id = 0;
            $lottery_order_model->save();

            // 清除排行榜缓存
            $this->clearCacheByTag('ranks');

            // 记录积分变动
            $this->addIntergralRecord([
                'user_id' => $this->user_id,
                'user_tg_id' => $this->telegram_id,
                'transaction_id' => $transaction_id,
                'transaction_integral_amount' => $integral
            ], commonDict::INTEGRAL_TYPE_FREE);  // 1-首次免费抽奖获得

            Db::commit();

            $this->releaseLock($lockKey);
        } catch (\Exception $e) {
            Db::rollback();

            $this->releaseLock($lockKey);
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

        // 获取Redis锁，防止并发抽奖导致积分扣减不正确
        $lockKey = 'lottery:' . $this->user_id;
        if (!$this->getLock($lockKey, 30)) { // 30秒锁过期时间
            throw new ApiException('System busy, please try again later');
        }

        // 记录抽奖结果
        Db::startTrans();
        try {
            // 再次检查用户积分，确保足够支付
            $current_integral = Users::where('id', $this->user_id)->value('integral_num');
            if ($current_integral < $latteryType['pay_integral']) {
                throw new ApiException('Insufficient integral');
            }

            $transaction_id = (new Snowflake())->id();
            // 记录积分变动
            $this->addIntergralRecord([
                'user_id' => $this->user_id,
                'user_tg_id' => $this->telegram_id,
                'transaction_id' => $transaction_id,
                'transaction_integral_amount' => -$latteryType['pay_integral']
            ], commonDict::INTEGRAL_TYPE_LOTTERY);

            // 获取该档位下的所有奖品
            $gifts = $this->giftService->getGiftsByType($latteryType['id']);
            if (empty($gifts)) {
                throw new ApiException('Gift not found');
            }

            // 执行抽奖逻辑
            $award = $this->lottery($gifts);

            if (empty($award)) {
                throw new ApiException('Gift not found');
            }

            $lottery_order_model = new LotteryOrder();

            $lottery_order_model->user_id = $this->user_id;
            $lottery_order_model->user_tg_id = $this->telegram_id;
            $lottery_order_model->pay_integral = $latteryType['pay_integral'];
            $lottery_order_model->order_uuid = $transaction_id;
            $lottery_order_model->lottery_type_id = $latteryType['id'];
            $lottery_order_model->award_type = $award['award_type'];

            if ($award['award_type'] == lotteryDict::AWARD_TYPE_GIFT) {
                $lottery_order_model->gift_id = $award['id'];
                $lottery_order_model->gift_tg_id = $award['gift_tg_id'];
                $lottery_order_model->gift_is_limit = $award['is_limit'];
                $lottery_order_model->award_star = $award['star_price'];

                // 清除用户礼物列表缓存
                $this->clearCacheByTag('user_gifts:' . $this->user_id);
            } else {
                $lottery_order_model->award_integral = $award['integral_price'];
            }

            $lottery_order_model->save();

            // 清除排行榜缓存
            $this->clearCacheByTag('ranks');

            if ($award['integral_price'] > 0) {
                $this->addIntergralRecord([
                    'user_id' => $this->user_id,
                    'user_tg_id' => $this->telegram_id,
                    'transaction_id' => $transaction_id,
                    'transaction_integral_amount' => $award['integral_price'],
                ], commonDict::INTEGRAL_TYPE_LOTTERY_AWARD);
            }

            $award['lottery_id'] = $lottery_order_model->id;

            Db::commit();

            $this->releaseLock($lockKey);
        } catch (\Exception $e) {
            Db::rollback();

            $this->releaseLock($lockKey);
            Log::error('【积分抽奖失败】：' . $e->getMessage());
            throw new ApiException('Lottery failed');
        }
        return $award;
    }


    public function addIntergralRecord($integral_info, $add_type)
    {
        // 记录积分变动
        $integralRecord = new IntegralRecord();
        $integralRecord->user_id = $integral_info['user_id'];
        $integralRecord->user_tg_id = $integral_info['user_tg_id'];
        $integralRecord->change_num = $integral_info['transaction_integral_amount'];
        $integralRecord->transaction_id = $integral_info['transaction_id'];
        $integralRecord->type = $add_type;
        $integralRecord->save();
        // 更新用户积分
        if ($integral_info['transaction_integral_amount'] > 0) {
            Users::where('id', $integral_info['user_id'])->inc('integral_num', $integral_info['transaction_integral_amount'])->update([]);
        } else {
            Users::where('id', $integral_info['user_id'])->dec('integral_num', abs($integral_info['transaction_integral_amount']))->update([]);
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
        // 构建缓存键，包含用户ID、是否限量标志和分页参数
        $page_params = $this->getPageParam();
        $cache_key = 'user_gifts:' . $this->user_id . ':' . ($is_limit ? 'limit' : 'all') . ':' . $page_params['page'] . ':' . $page_params['limit'];

        // 使用缓存，过期时间设为10分钟，使用用户ID作为标签便于清除
        return $this->getCache($cache_key, function () use ($is_limit) {
            $where = [["award_status", "=", 0], ["award_type", "=", lotteryDict::AWARD_TYPE_GIFT], ["user_id", "=", $this->user_id], ["gift_id", "<>", 0]];
            if ($is_limit) {
                array_push($where, ["gift_is_limit", "=", 1]);
            }
            $user_gifts_model = LotteryOrder::where($where)
                ->with(['gifts'])
                ->order('create_time', 'desc')
                ->field('id,user_id,user_tg_id,gift_id,gift_tg_id,award_star');
            $list = $this->pageQuery($user_gifts_model);
            return $list;
        }, 600, 'user_gifts:' . $this->user_id);
    }

    public function getUserAllGifts()
    {
        // 构建缓存键，包含用户ID和分页参数
        $page_params = $this->getPageParam();
        $cache_key = 'user_all_gifts:' . $this->user_id . ':' . $page_params['page'] . ':' . $page_params['limit'];

        // 使用缓存，过期时间设为10分钟，使用用户ID作为标签便于清除
        return $this->getCache($cache_key, function () {
            $user_limit_gifts = $this->getUserGifts(is_limit: true);
            $user_all_gifts = $this->getUserGifts(is_limit: false);

            return [
                "limit_gifts" => $user_limit_gifts,
                "all_gifts" => $user_all_gifts
            ];
        }, 600, 'user_gifts:' . $this->user_id);
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
            // ->append(['gift_animation'])
            ->findOrEmpty()
            ->toArray();
        if (empty($order_info)) {
            throw new ApiException('Not found');
        }

        // 获取Redis锁，防止重复兑换礼物
        $lockKey = 'gift_exchange:' . $this->user_id . ':' . $id;
        if (!$this->getLock($lockKey, 30)) { // 30秒锁过期时间
            throw new ApiException('System busy, please try again later');
        }

        try {
            // 再次检查订单状态，确保未被兑换
            $current_order = LotteryOrder::where(["user_id" => $this->user_id, "id" => $id, 'award_status' => 0])
                ->findOrEmpty()
                ->toArray();
            if (empty($current_order)) {
                $this->releaseLock($lockKey);
                throw new ApiException('Gift already exchanged or not found');
            }

            $this->sendGiftToUser($order_info['user_tg_id'], $order_info['gift_tg_id']);
            LotteryOrder::where(["user_id" => $this->user_id, "id" => $id, 'award_status' => 0])
                ->update(['award_status' => commonDict::AWARD_STATUS_TO_GIFT, 'award_time' => time()]);

            $this->releaseLock($lockKey);
        } catch (\Exception $e) {

            $this->releaseLock($lockKey);
            LotteryOrder::where(["user_id" => $this->user_id, "id" => $id, 'award_status' => 0])
                ->update(['award_error_remark' => $e->getMessage()]);
            Log::error('【礼物兑换失败】：' . $e->getMessage());
            throw new ApiException('Gift exchange failed');
        }
        return [
            // "gift_animation" => $order_info['gift_animation'],
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

        // 获取Redis锁，防止重复兑换积分
        $lockKey = 'gift_to_integral:' . $this->user_id . ':' . $id;
        if (!$this->getLock($lockKey, 30)) { // 30秒锁过期时间
            throw new ApiException('System busy, please try again later');
        }

        try {
            // 再次检查订单状态，确保未被兑换
            $current_order = LotteryOrder::where(["user_id" => $this->user_id, "id" => $id, 'award_status' => 0])
                ->findOrEmpty()
                ->toArray();
            if (empty($current_order)) {
                $this->releaseLock($lockKey);
                throw new ApiException('Gift already exchanged or not found');
            }

            LotteryOrder::where(["user_id" => $this->user_id, "id" => $id, 'award_status' => 0])
                ->update(['award_status' => commonDict::AWARD_STATUS_TO_INTEGRAL, 'award_time' => time()]);
            // 记录积分变动
            $this->addIntergralRecord([
                'user_id' => $this->user_id,
                'user_tg_id' => $this->telegram_id,
                'transaction_id' => $order_info['order_uuid'],
                'transaction_integral_amount' => $order_info['award_star'],
            ], commonDict::INTEGRAL_TYPE_GIFT);


            $this->releaseLock($lockKey);
        } catch (\Exception $e) {

            $this->releaseLock($lockKey);
            Log::error('【礼物兑换积分失败】：' . $e->getMessage());
            throw new ApiException('Gift to integral exchange failed');
        }
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