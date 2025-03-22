<?php
declare (strict_types = 1);

namespace app\service;

use app\model\Gifts;
use app\model\LotteryType;
use \TelegramBot\Api\BotApi;

class GiftService extends BaseService
{
    public function __construct()
    {
        parent::__construct();
        $this->model = new Gifts();
        $this->telegram = new BotApi(env('telegram.token'));
    }

    /**
     * 获取所有置顶奖品
     * @return array
     */
    public function getTopGifts()
    {
        return $this->getCache('gift:top_gifts', function() {
            return $this->model->where('top_show', 1)
                ->order(['star_price'=>'asc',"probability"=>"asc"])
                ->field("id,star_price,gift_tg_id,probability,emoji")
                // ->append(['gift_animation'])
                ->select()
                ->toArray();
        }, 3600, 'gifts');
    }
    
    /**
     * 获取所有抽奖档位
     * @return array
     */
    public function getAllTypes()
    {
        return $this->getCache('lottery:all_types', function() {
            return LotteryType::where('show', 1)
                ->order(['sort'=>"asc","pay_integral"=>"asc"])
                ->withoutField("create_time,update_time")
                ->select()
                ->toArray();
        }, 3600, 'lottery_types');
    }
    
    /**
     * 根据档位ID获取奖品列表
     * @param int $typeId 档位ID
     * @return array
     */
    public function getGiftsByType(int $typeId)
    {
        return $this->getCache('gift:type_'.$typeId, function() use ($typeId) {
            return $this->model->where('lottery_type_id', $typeId)
                ->withoutField("create_time,update_time")
                ->order(['probability'=>'asc'])
                ->select()
                ->toArray();
        }, 3600, 'gifts');
    }
    
    /**
     * 获取所有档位及其对应的奖品信息
     * @return array
     */
    public function getAllTypesWithGifts()
    {
        return $this->getCache('lottery:types_with_gifts', function() {
            return LotteryType::where('show', 1)
                ->with(['gifts'])
                ->withoutField("create_time,update_time")
                ->order(['sort'=>'asc','pay_integral'=>'asc'] )
                ->select()
                ->toArray();
        }, 3600, 'lottery_types');
    }

    public function getGiftAnimation($gift_tg_id)
    {
        return [
            'gift_tg_id' => $gift_tg_id,
            'gift_animation' =>  getGiftAnimationString($gift_tg_id)
        ];
        // return getGiftAnimationTgs($gift_tg_id);
    }

}