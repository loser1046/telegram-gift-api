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
            ->setDescription('the gift:syn command')
            ->addArgument('test', Argument::OPTIONAL, "是否Telegram测试数据,test:测试 pro:正式");
    }

    protected function execute(Input $input, Output $output)
    {
        // 指令输出
        //接收test参数
        $test = $input->getArgument('test');
        if(!$test){
            $output->error('请输入test参数');
            return;
        }
        $test = $test == 'test';
        $giftService = new TelegramGiftListsService();
        $output->writeln('开始同步礼物列表。。。');
        $lists = $giftService->telegramGiftSyn($test);
        if (empty($lists)) {
            $output->writeln('礼物列表为空~');
        }
        $output->writeln('礼物列表同步完成。。。');
        $output->writeln('开始同步礼物动画。。。');
        $giftService->telegramGiftAnimationSyn($lists,$test);
        $output->writeln('礼物动画同步完成。。。');
    }
}
