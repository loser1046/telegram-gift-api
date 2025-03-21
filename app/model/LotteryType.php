<?php
declare (strict_types = 1);

namespace app\model;

use think\Model;

/**
 * @mixin \think\Model
 */
class LotteryType extends Model
{
    protected $name = 'lottery_type';
    
    /**
     * 定义与奖品的关联
     */
    public function gifts()
    {
        return $this->hasMany(Gifts::class, 'lottery_type_id', 'id')
        ->withoutField('create_time,update_time');
    }
}