<?php
declare(strict_types=1);

namespace app\dict;

class lotteryDict
{
    /***************************奖品类型*************************/
    //礼物
    const AWARD_TYPE_GIFT = 1;
    //积分
    const AWARD_TYPE_INTEGRAL = 2;

    /***************************奖品列表*************************/

    const LOTTERY_LIST = [
        [
            'lottery_integral' => 15,
            'lottery' => [
                [
                    'award_type' => self::AWARD_TYPE_INTEGRAL,
                    'integral_price' => 5,
                    'star_price' => 0,
                    'probability' => 56.34,
                    'tg_gift_ids' => [
                    ],
                ],
                [
                    'award_type' => self::AWARD_TYPE_INTEGRAL,
                    'integral_price' => 10,
                    'star_price' => 0,
                    'probability' => 36.72,
                    'tg_gift_ids' => [
                    ],
                ],
                [
                    'award_type' => self::AWARD_TYPE_GIFT,
                    'integral_price' => 0,
                    'star_price' => 15,
                    'probability' => 2.9,
                    'tg_gift_ids' => [
                        "5170145012310081615",
                        "5170233102089322756"
                    ],
                ],
                [
                    'award_type' => self::AWARD_TYPE_GIFT,
                    'integral_price' => 0,
                    'star_price' => 25,
                    'probability' => 2.88,
                    'tg_gift_ids' => [
                        "5170250947678437525",
                        "5168103777563050263"
                    ],
                ],
                [
                    'award_type' => self::AWARD_TYPE_GIFT,
                    'integral_price' => 0,
                    'star_price' => 50,
                    'probability' => 1.16,
                    'tg_gift_ids' => [
                        "5170144170496491616",
                        "5170314324215857265",
                        "5170564780938756245",
                        "6028601630662853006"
                    ],
                ],
            ]

        ],
        [
            'lottery_integral' => 25,
            'lottery' => [
                [
                    'award_type' => self::AWARD_TYPE_INTEGRAL,
                    'integral_price' => 5,
                    'star_price' => 0,
                    'probability' => 28.79,
                    'tg_gift_ids' => [
                    ],
                ],
                [
                    'award_type' => self::AWARD_TYPE_INTEGRAL,
                    'integral_price' => 10,
                    'star_price' => 0,
                    'probability' => 28.79,
                    'tg_gift_ids' => [
                    ],
                ],
                [
                    'award_type' => self::AWARD_TYPE_GIFT,
                    'integral_price' => 0,
                    'star_price' => 15,
                    'probability' => 23.16,
                    'tg_gift_ids' => [
                        "5170233102089322756",
                        "5170145012310081615",
                    ],
                ],
                [
                    'award_type' => self::AWARD_TYPE_GIFT,
                    'integral_price' => 0,
                    'star_price' => 25,
                    'probability' => 14.82,
                    'tg_gift_ids' => [
                        "5170250947678437525",
                        "5168103777563050263"
                    ],
                ],
                [
                    'award_type' => self::AWARD_TYPE_GIFT,
                    'integral_price' => 0,
                    'star_price' => 50,
                    'probability' => 3.14,
                    'tg_gift_ids' => [
                        "5170144170496491616",
                        "5170314324215857265",
                        "5170564780938756245",
                        "6028601630662853006",
                    ],
                ],
                [
                    'award_type' => self::AWARD_TYPE_GIFT,
                    'integral_price' => 0,
                    'star_price' => 100,
                    'probability' => 1.3,
                    'tg_gift_ids' => [
                        "5168043875654172773",
                        "5170690322832818290",
                        "5170521118301225164"
                    ],
                ],
            ]
        ],
        [
            'lottery_integral' => 50,
            'lottery' => [
                [
                    'award_type' => self::AWARD_TYPE_INTEGRAL,
                    'integral_price' => 5,
                    'star_price' => 0,
                    'probability' => 22.36,
                    'tg_gift_ids' => [
                    ],
                ],
                [
                    'award_type' => self::AWARD_TYPE_INTEGRAL,
                    'integral_price' => 10,
                    'star_price' => 0,
                    'probability' => 22.36,
                    'tg_gift_ids' => [
                    ],
                ],
                [
                    'award_type' => self::AWARD_TYPE_INTEGRAL,
                    'integral_price' => 80,
                    'star_price' => 0,
                    'probability' => 5.38,
                    'tg_gift_ids' => [
                    ],
                ],
                [
                    'award_type' => self::AWARD_TYPE_INTEGRAL,
                    'integral_price' => 200,
                    'star_price' => 0,
                    'probability' => 2.69,
                    'tg_gift_ids' => [
                    ],
                ],
                [
                    'award_type' => self::AWARD_TYPE_GIFT,
                    'integral_price' => 0,
                    'star_price' => 15,
                    'probability' => 18.98,
                    'tg_gift_ids' => [
                        "5170233102089322756",
                        "5170145012310081615",
                    ],
                ],
                [
                    'award_type' => self::AWARD_TYPE_GIFT,
                    'integral_price' => 0,
                    'star_price' => 25,
                    'probability' => 15.78,
                    'tg_gift_ids' => [
                        "5170250947678437525",
                        "5168103777563050263"
                    ],
                ],
                [
                    'award_type' => self::AWARD_TYPE_GIFT,
                    'integral_price' => 0,
                    'star_price' => 50,
                    'probability' => 5.23,
                    'tg_gift_ids' => [
                        "5170144170496491616",
                        "5170314324215857265",
                        "5170564780938756245",
                        "6028601630662853006",
                    ],
                ],
                [
                    'award_type' => self::AWARD_TYPE_GIFT,
                    'integral_price' => 0,
                    'star_price' => 100,
                    'probability' => 4.23,
                    'tg_gift_ids' => [
                        "5168043875654172773",
                        "5170690322832818290",
                        "5170521118301225164"
                    ],
                ],
                [
                    'award_type' => self::AWARD_TYPE_GIFT,
                    'integral_price' => 0,
                    'star_price' => 150,
                    'probability' => 1.56,
                    'tg_gift_ids' => [
                        "5782988952268964995",
                    ],
                ],
                [
                    'award_type' => self::AWARD_TYPE_GIFT,
                    'integral_price' => 0,
                    'star_price' => 250,
                    'probability' => 0.74,
                    'tg_gift_ids' => [
                        "5963238670868677492",
                    ],
                ],
                [
                    'award_type' => self::AWARD_TYPE_GIFT,
                    'integral_price' => 0,
                    'star_price' => 350,
                    'probability' => 0.55,
                    'tg_gift_ids' => [
                        "5782984811920491178",
                    ],
                ],
                [
                    'award_type' => self::AWARD_TYPE_GIFT,
                    'integral_price' => 0,
                    'star_price' => 500,
                    'probability' => 0.13,
                    'tg_gift_ids' => [
                        "5783075783622787539",
                    ],
                ],
            ]
        ],
        [
            'lottery_integral' => 100,
            'lottery' => [
                [
                    'award_type' => self::AWARD_TYPE_INTEGRAL,
                    'integral_price' => 10,
                    'star_price' => 0,
                    'probability' => 24.79,
                    'tg_gift_ids' => [
                    ],
                ],
                [
                    'award_type' => self::AWARD_TYPE_INTEGRAL,
                    'integral_price' => 80,
                    'star_price' => 0,
                    'probability' => 7.84,
                    'tg_gift_ids' => [
                    ],
                ],
                [
                    'award_type' => self::AWARD_TYPE_INTEGRAL,
                    'integral_price' => 200,
                    'star_price' => 0,
                    'probability' => 5.27,
                    'tg_gift_ids' => [
                    ],
                ],
                [
                    'award_type' => self::AWARD_TYPE_GIFT,
                    'integral_price' => 0,
                    'star_price' => 15,
                    'probability' => 20.76,
                    'tg_gift_ids' => [
                        "5170233102089322756",
                        "5170145012310081615",
                    ],
                ],
                [
                    'award_type' => self::AWARD_TYPE_GIFT,
                    'integral_price' => 0,
                    'star_price' => 25,
                    'probability' => 12.98,
                    'tg_gift_ids' => [
                        "5170250947678437525",
                        "5168103777563050263"
                    ],
                ],
                [
                    'award_type' => self::AWARD_TYPE_GIFT,
                    'integral_price' => 0,
                    'star_price' => 50,
                    'probability' => 9.28,
                    'tg_gift_ids' => [
                        "5170144170496491616",
                        "5170314324215857265",
                        "5170564780938756245",
                        "6028601630662853006",
                    ],
                ],
                [
                    'award_type' => self::AWARD_TYPE_GIFT,
                    'integral_price' => 0,
                    'star_price' => 100,
                    'probability' => 6.76,
                    'tg_gift_ids' => [
                        "5168043875654172773",
                        "5170690322832818290",
                        "5170521118301225164"
                    ],
                ],
                [
                    'award_type' => self::AWARD_TYPE_GIFT,
                    'integral_price' => 0,
                    'star_price' => 150,
                    'probability' => 5.39,
                    'tg_gift_ids' => [
                        "5782988952268964995",
                    ],
                ],
                [
                    'award_type' => self::AWARD_TYPE_GIFT,
                    'integral_price' => 0,
                    'star_price' => 250,
                    'probability' => 4.14,
                    'tg_gift_ids' => [
                        "5963238670868677492",
                    ],
                ],
                [
                    'award_type' => self::AWARD_TYPE_GIFT,
                    'integral_price' => 0,
                    'star_price' => 350,
                    'probability' => 2.75,
                    'tg_gift_ids' => [
                        "5782984811920491178",
                    ],
                ],
                [
                    'award_type' => self::AWARD_TYPE_GIFT,
                    'integral_price' => 0,
                    'star_price' => 500,
                    'probability' => 0.04,
                    'tg_gift_ids' => [
                        "5783075783622787539",
                    ],
                ],
            ]
        ],
    ];

}