<?php
declare (strict_types = 1);

namespace app\command\Gift;

use app\dict\giftDict;
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
    protected function configure()
    {
        // 指令配置
        $this->setName('gift:initGifts')
            ->setDescription('the gift:initGifts command');
    }

    protected function execute(Input $input, Output $output)
    {
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
        $gift_types = LotteryType::select()->toArray();
        if (empty($gift_types)) {
            $output->writeln('抽奖类型为空！！！');
            return false;
        }
        $this->insertGiftsByDict($gift_types, $telegram_gift_lists);
        $output->writeln('写入成功');
    }

    protected function insertGiftsByDict($gift_types, $telegram_gift_lists)
    {
        $insert = [];
        $gift_list = giftDict::GIFT_LIST;
        foreach ($gift_list as $lottery_key => $lottery_list) {
            $integral = $lottery_list['integral'];
            $gift_type = $this->getGifttypeByLotteryintegral($integral, $gift_types);
            foreach ($lottery_list['lottery'] as $lottery_info) {
                $probability = $lottery_info['probability'];
                foreach ($lottery_info['gifts'] as $gift_one) {
                    $gift_one_info = $this->getGiftinfoByGifttgid($gift_one, $telegram_gift_lists);
                    if (empty($gift_one_info)) {
                        continue;
                    }
                    var_dump($gift_one_info);
                    $insert[] = [
                        "gift_type" => $gift_type,
                        "probability" => $probability,
                        "star_price" => $gift_one_info["star_count"],
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
            }
        }
        Gifts::insertAll($insert);

    }

    /**
     * 根据抽奖类型随机写入奖品
     *
     * @return void
     */
    protected function insertGiftsByRandom($gift_types, $telegram_gift_lists)
    {
        $insert = [];
        foreach ($gift_types as $gift_type) {
            // 随机打乱数组并截取前10条数据
            $random_keys = array_rand($telegram_gift_lists, min(10, count($telegram_gift_lists)));
            foreach ((array)$random_keys as $key) {
                $gift_one = $telegram_gift_lists[$key];
                $insert[] = [
                    "gift_type" => $gift_type['id'],
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

    protected function getGifttypeByLotteryintegral($lottery_integral, $gift_types)
    {
        foreach ($gift_types as $gift_type) {
            if ($gift_type["pay_integral"] == $lottery_integral) {
                return $gift_type["id"];
            }
        }
        return [];
    }
}
