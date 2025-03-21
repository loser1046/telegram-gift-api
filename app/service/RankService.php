<?php
declare(strict_types=1);

namespace app\service;

use app\exception\ApiException;
use app\model\LotteryOrder;
use app\model\TgStarTransactions;
use app\model\LotteryType;

class RankService extends BaseService
{

    /**
     * 获取指定类型的排行榜数据
     * @param int $typeId 类型ID
     * @return array
     */
    public function getRankList(int $typeId = 0)
    {
        // 获取所有启用的抽奖类型
        $types = LotteryType::where('show', 1);
        if ($typeId > 0) {
            $types = $types->where('id', $typeId);
        }
        $types = $types->column('*', 'id');

        if (empty($types)) {
            throw new ApiException('No lottery types found');
        }

        $result = [];
        foreach ($types as $type) {
            $rankQuery = LotteryOrder::with([
                'user' => function ($query) {
                    $query->field(['id', 'first_name', 'last_name', 'nick_name', 'username', 'photo_url', 'is_premium']);
                }
            ])
                ->where([
                    'lottery_type_id' => $type['id']
                ])
                ->field([
                    'user_id',
                    'lottery_type_id',
                    'COUNT(*) as lottery_count',
                    'SUM(gift_is_limit) as limit_raward_count',
                    'SUM(pay_integral) as total_cost',
                    'SUM(award_star) as total_award'
                ])
                ->group('user_id')
                ->order('lottery_count', 'desc')
                ->order('total_award', 'desc');

            $ranks = $this->pageQuery($rankQuery);

            // 计算排名
            $rankList = [];
            foreach ($ranks["data"] as $key => $rank) {
                $rank['rank'] = ($ranks['current_page'] - 1) * $ranks['per_page'] + $key + 1;
                $rankList[] = $rank;
            }

            $result[$type['id']] = [
                "current_page" => $ranks['current_page'],
                "per_page" => $ranks['per_page'],
                "total" => $ranks['total'],
                "last_page" => $ranks['last_page'],
                'type_name' => $type['type_name'],
                'type_id' => $type['id'],
                'ranks' => $rankList
            ];

            // 如果指定了类型ID，只返回该类型的排行榜
            if ($typeId > 0) {
                return $result[$typeId];
            }
        }

        return $result;
    }

    /**
     * 获取所有档位的排行榜数据
     * @return array
     */
    public function getAllRankList()
    {
        return $this->getRankList(0);
    }

    /**
     * 获取所有档位信息
     * @return array
     */
    public function getAllTypes()
    {
        return (new GiftService())->getAllTypes();
    }
}