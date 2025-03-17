<?php
declare (strict_types = 1);

namespace app\model;

use think\Model;

/**
 * @mixin \think\Model
 */
class Gifts extends Model
{
    protected $name = 'gifts';
    

    /**
     * 定义与奖品档位的关联
     */
    public function giftType()
    {
        return $this->belongsTo(GiftType::class, 'gift_type', 'id');
    }
}
