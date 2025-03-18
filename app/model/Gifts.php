<?php
declare (strict_types = 1);

namespace app\model;

use think\facade\Log;
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
        return $this->belongsTo(LotteryType::class, 'id', 'lottery_type_id');
    }

    // public function getGiftAnimationAttr($value, $data)
	// {
    //     if ($data["gift_tg_id"]) {
    //         $json_file_path = public_path() . 'static/' . $data["gift_tg_id"] . '.json';
    //         if (file_exists($json_file_path)) {
    //             return file_get_contents($json_file_path);
    //         } else {
    //             Log::error('【Gift animation JSON file not found】: ' . $json_file_path);
    //         }
    //     }
    //     return "";
	// }
}
