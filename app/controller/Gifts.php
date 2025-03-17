<?php

namespace app\controller;

use app\BaseController;
use app\service\GiftService;
use app\service\LotteryService;
use \TelegramBot\Api\BotApi;

class Gifts extends BaseController
{

    protected $telegram;
    protected $giftService;
    protected $lotteryService;

    public function __construct()
    {
        parent::__construct(app());
        $this->telegram = new BotApi(env('telegram.token'),'2200069667:AAG3NXkZiF3ms75TfDeYbUewwExMC8lN0V8');
        $this->giftService = new GiftService();
        $this->lotteryService = new LotteryService();
    }
    
    /**
     * 获取所有奖品档位及其对应的奖品信息
     */
    public function index()
    {
        $result = $this->giftService->getAllTypesWithGifts();
        return success($result);
    }
    
    /**
     * 获取所有抽奖档位
     */
    public function getTypes()
    {
        $result = $this->giftService->getAllTypes();
        return success($result);
    }
    
    /**
     * 获取指定档位的奖品列表
     */
    public function getGiftsByType($type_id)
    {
        
        $result = $this->giftService->getGiftsByType($type_id);
        return success($result);
    }

    /**
     * 执行抽奖
     */
    public function doLottery($type_id)
    {
        $result = $this->lotteryService->createTransaction($type_id);
        
        return success($result);
    }

}
