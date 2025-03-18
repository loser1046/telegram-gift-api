<?php
declare (strict_types = 1);

namespace app\command\Gift;

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
        $gift_types = GiftType::select()->toArray();
        if (empty($gift_types)) {
            $output->writeln('奖品类型为空！！！');
            return false;
        }
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
                    "chance" => "",
                    "chance_cmp" => "",
                    "top_show" => 0
                ];
            }
        }
        Gifts::insertAll($insert);
        $output->writeln('写入成功');
    }
}
