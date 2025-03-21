<?php
declare(strict_types=1);

namespace app\command\Gift;

use app\dict\lotteryDict;
use app\model\Gifts;
use app\model\LotteryType;
use app\model\TelegramGiftLists;
use think\console\Command;
use think\console\Input;
use think\console\input\Argument;
use think\console\input\Option;
use think\console\Output;

class InitGifts extends Command
{
    protected $output;
    protected function configure()
    {
        // 指令配置
        $this->setName('gift:initGifts')
            ->setDescription('the gift:initGifts command');
    }

    protected function execute(Input $input, Output $output)
    {
        $this->output = $output;

        // 指令输出
        // $output->writeln('gift:initGifts');
        $telegram_gift_lists = TelegramGiftLists::select()->toArray();
        if (empty($telegram_gift_lists)) {
            $output->writeln('Telegram奖品列表为空！！！');
            return false;
        }
        $gifts = Gifts::select()->toArray();
        if (!empty($gifts)) {
            $output->writeln('奖品列表已有数据！！！');
            return false;
        }
        $lottery_types = LotteryType::select()->toArray();
        if (empty($lottery_types)) {
            $output->writeln('抽奖类型为空！！！');
            return false;
        }
        $this->insertGiftsByDict($lottery_types, $telegram_gift_lists);
        $output->writeln('写入成功');
    }

    protected function insertGiftsByDict($lottery_types, $telegram_gift_lists)
    {
        $insert = [];
        $lottery_list = lotteryDict::LOTTERY_LIST;
        foreach ($lottery_list as $lottery_key => $lottery_items) {
            $lottery_integral = $lottery_items['lottery_integral'];
            $lottery_type_id = $this->getGifttypeByLotteryintegral($lottery_integral, $lottery_types);
            foreach ($lottery_items['lottery'] as $lottery_info) {
                $award_type = $lottery_info['award_type'];
                $probability = $lottery_info['probability'];
                if ($award_type == lotteryDict::AWARD_TYPE_GIFT) {
                    foreach ($lottery_info['tg_gift_ids'] as $gift_tg_id) {
                        $gift_one_info = $this->getGiftinfoByGifttgid($gift_tg_id, $telegram_gift_lists);
                        if (empty($gift_one_info)) {
                            $this->output->writeln('奖品tgid不存在--' . $gift_tg_id . '--');
                            continue;
                        }
                        // var_dump($gift_one_info);
                        $insert[] = [
                            "lottery_type_id" => $lottery_type_id,
                            "award_type" => $award_type,
                            "probability" => $probability,
                            "star_price" => $gift_one_info["star_count"],
                            "integral_price" => 0,
                            "is_limit" => $gift_one_info["is_limit"],
                            "occurrence_num" => 0,
                            "gift_tg_id" => $gift_one_info["gift_tg_id"],
                            "emoji" => $gift_one_info["emoji"],
                            "custom_emoji_id" => $gift_one_info["custom_emoji_id"],
                            "file_id" => $gift_one_info["file_id"],
                            "file_unique_id" => $gift_one_info["file_unique_id"],
                            "star_count" => $gift_one_info["star_count"],
                            "upgrade_star_count" => $gift_one_info["upgrade_star_count"],
                            "top_show" => 0,
                            "create_time" => time(),
                            "update_time" => time()
                        ];
                    }
                } else {
                    $insert[] = [
                        "lottery_type_id" => $lottery_type_id,
                        "award_type" => $award_type,
                        "probability" => $probability,
                        "star_price" => 0,
                        "integral_price" => $lottery_info['integral_price'],
                        "is_limit" => 0,
                        "occurrence_num" => 0,
                        "gift_tg_id" => 0,
                        "emoji" => '',
                        "custom_emoji_id" => '',
                        "file_id" => '',
                        "file_unique_id" => '',
                        "star_count" => 0,
                        "upgrade_star_count" => 0,
                        "top_show" => 0,
                        "create_time" => time(),
                        "update_time" => time()
                    ];
                }

            }
        }
        if (empty($insert)) {
            $this->output->writeln('写入数据为空！！！');
            return false;
        }
        var_dump($insert);
        Gifts::insertAll($insert);
    }

    /**
     * 根据抽奖类型随机写入奖品
     *
     * @return void
     */
    protected function insertGiftsByRandom($lottery_types, $telegram_gift_lists)
    {
        $insert = [];
        foreach ($lottery_types as $lottery_type) {
            // 随机打乱数组并截取前10条数据
            $random_keys = array_rand($telegram_gift_lists, min(10, count($telegram_gift_lists)));
            foreach ((array) $random_keys as $key) {
                $gift_one = $telegram_gift_lists[$key];
                $insert[] = [
                    "lottery_type_id" => $lottery_type['id'],
                    "probability" => round(mt_rand(1, 10) / 100, 2),
                    "star_price" => $gift_one["star_count"],
                    "is_limit" => $gift_one["is_limit"],
                    "occurrence_num" => 0,
                    "gift_tg_id" => $gift_one["gift_tg_id"],
                    "emoji" => $gift_one["emoji"],
                    "custom_emoji_id" => $gift_one["custom_emoji_id"],
                    "file_id" => $gift_one["file_id"],
                    "file_unique_id" => $gift_one["file_unique_id"],
                    "star_count" => $gift_one["star_count"],
                    "upgrade_star_count" => $gift_one["upgrade_star_count"],
                    "top_show" => 0
                ];
            }
        }
        Gifts::insertAll($insert);
    }

    protected function getGiftinfoByGifttgid($gift_tg_id, $telegram_gift_lists)
    {
        foreach ($telegram_gift_lists as $gift) {
            if ($gift["gift_tg_id"] == $gift_tg_id) {
                return $gift;
            }
        }
        return [];
    }

    protected function getGifttypeByLotteryintegral($lottery_integral, $lottery_types)
    {
        foreach ($lottery_types as $lottery_type) {
            if ($lottery_type["pay_integral"] == $lottery_integral) {
                return $lottery_type["id"];
            }
        }
        return [];
    }
}
