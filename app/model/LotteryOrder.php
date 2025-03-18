<?php
declare (strict_types = 1);

namespace app\model;

use think\facade\Log;
use think\Model;

/**
 * @mixin \think\Model
 */
class LotteryOrder extends Model
{
    protected $name = 'lottery_order';
    
    /**
     * 定义与奖品的关联
     */
    public function gifts()
    {
        return $this->hasOne(Gifts::class, 'id', 'gift_id')
            ->withoutField('create_time,update_time');
    }

    public function user()
    {
        return $this->hasOne(Users::class, 'id', 'user_id')->joinType('left');
            // ->withField('id,first_name,last_name,nick_name,username,photo_url,is_premium');
    }

    public function getGiftAnimationAttr($value, $data)
	{
        if ($data["gift_tg_id"]) {
            $json_file_path = public_path() . 'static/' . $data["gift_tg_id"] . '.json';
            if (file_exists($json_file_path)) {
                return file_get_contents($json_file_path);
            } else {
                Log::error('【Gift animation JSON file not found】: ' . $json_file_path);
            }
        }
        return "";
	}
}