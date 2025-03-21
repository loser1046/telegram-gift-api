<?php
declare(strict_types=1);

namespace app\dict;

class commonDict
{
    /***************************积分流水类型*************************/
    //首次抽奖获得
    const INTEGRAL_TYPE_FREE = 1;
    //抽奖消耗
    const INTEGRAL_TYPE_LOTTERY = 2;
    //充值
    const INTEGRAL_TYPE_TRANSACTION = 3;
    //分解礼物获得
    const INTEGRAL_TYPE_GIFT = 4;
    //抽奖获取
    const INTEGRAL_TYPE_LOTTERY_AWARD = 5;

    /***************************奖品使用状态*************************/
    //未使用
    const AWARD_STATUS_NOT_USE = 0;
    //已兑换为Gift
    const AWARD_STATUS_TO_GIFT = 1;
    //已兑换为积分
    const AWARD_STATUS_TO_INTEGRAL = 2;


}