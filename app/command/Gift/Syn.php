<?php
declare (strict_types = 1);

namespace app\command\Gift;

use app\service\TelegramGiftListsService;
use think\console\Command;
use think\console\Input;
use think\console\input\Argument;
use think\console\input\Option;
use think\console\Output;

class Syn extends Command
{
    protected function configure()
    {
        // 指令配置
        $this->setName('gift:syn')
            ->setDescription('the gift:syn command');
    }

    protected function execute(Input $input, Output $output)
    {
        // 指令输出
        // $output->writeln('gift:syn');
        $giftService = new TelegramGiftListsService();
        $giftService->telegramGiftSyn();
    }
}
