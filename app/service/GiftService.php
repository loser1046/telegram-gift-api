<?php
declare (strict_types = 1);

namespace app\service;

use app\model\Gifts;
use app\model\LotteryType;

class GiftService extends BaseService
{
    public function __construct()
    {
        parent::__construct();
        $this->model = new Gifts();
    }

    /**
     * 获取所有奖品
     * @return array
     */
    public function getAll()
    {
        $gifts = $this->model->select()->toArray();
        return $gifts;
    }

    /**
     * 获取所有置顶奖品
     * @return array
     */
    public function getTopGifts()
    {
        $gifts = $this->model->where('top_show', 1)
            ->order(['star_price'=>'asc',"probability"=>"asc"])
            ->field("id,star_price,gift_tg_id,probability,emoji")
            // ->append(['gift_animation'])
            ->select()
            ->toArray();
        return $gifts;
    }
    
    /**
     * 获取所有抽奖档位
     * @return array
     */
    public function getAllTypes()
    {
        $types = LotteryType::where('show', 1)
            ->order(['sort'=>"asc","pay_integral"=>"asc"])
            ->withoutField("create_time,update_time")
            ->select()
            ->toArray();
        return $types;
    }
    
    /**
     * 根据档位ID获取奖品列表
     * @param int $typeId 档位ID
     * @return array
     */
    public function getGiftsByType(int $typeId)
    {
        $gifts = $this->model->where('gift_type', $typeId)
            ->withoutField("create_time,update_time")
            ->order(['star_price'=>'asc'])
            ->select()
            ->toArray();
        return $gifts;
    }
    
    /**
     * 获取所有档位及其对应的奖品信息
     * @return array
     */
    public function getAllTypesWithGifts()
    {
        $types = LotteryType::where('show', 1)
            ->with(['gifts'])
            ->withoutField("create_time,update_time")
            ->order(['sort'=>'asc','pay_integral'=>'asc'] )
            ->select()
            ->toArray();
        return $types;
    }

    public function getGiftAnimation($gift_tg_id)
    {
        return getGiftAnimationString($gift_tg_id);
    }

}