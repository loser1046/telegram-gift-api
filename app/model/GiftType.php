<?php
declare (strict_types = 1);

namespace app\model;

use think\Model;

/**
 * @mixin \think\Model
 */
class GiftType extends Model
{
    protected $name = 'gift_type';
    
    /**
     * 定义与奖品的关联
     */
    public function gifts()
    {
        return $this->hasMany(Gifts::class, 'gift_type', 'id');
    }
}