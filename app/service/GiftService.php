<?php
declare (strict_types = 1);

namespace app\service;

use app\model\Gifts;
use app\model\GiftType;
use think\facade\Log;
use think\Response;

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
            ->field("id,star_price,probability,emoji")
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
        $types = GiftType::where('show', 1)
            ->order(['sort'=>"asc","pay_star"=>"asc"])
            ->withoutField("created_at,updated_at")
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
            ->withoutField("created_at,updated_at")
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
        $types = GiftType::where('show', 1)
            ->with(['gifts'])
            ->order(['sort'=>'asc','pay_star'=>'asc'] )
            ->select()
            ->toArray();
        return $types;
    }
}