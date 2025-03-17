<?php
declare (strict_types = 1);

namespace app\model;

use think\Model;

/**
 * @mixin \think\Model
 */
class TgStarTransactions extends Model
{
    protected $name = 'tg_star_transactions';

    /**
	 * 礼物关联
	 * @return \think\model\relation\HasOne
	 */
	public function gifts()
	{
		return $this->hasOne(Gifts::class, 'id', 'gift_id')->joinType('left')
			->withField('id,gift_type,probability,star_price,is_limit,gift_tg_id,emoji,custom_emoji_id,file_id,file_unique_id');
	}

    public function user()
    {
        return $this->hasOne(Users::class, 'id', 'user_id')->joinType('left');
            // ->withField('id,first_name,last_name,nick_name,username,photo_url,is_premium');
    }

}
