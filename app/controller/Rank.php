<?php

namespace app\controller;

use app\BaseController;
use app\service\RankService;

class Rank extends BaseController
{
    protected $rankService;
    
    public function __construct()
    {
        parent::__construct(app());
        $this->rankService = new RankService();
    }
    
    /**
     * 获取所有档位信息
     * @return \think\Response
     */
    public function getTypes()
    {
        $types = $this->rankService->getAllTypes();
        return success($types);
    }
    
    /**
     * 获取排行榜数据
     * @return \think\Response
     */
    public function getRankList(int $type_id)
    {
        if ($type_id <= 0) {
            return fail("Invalid type");
        }
        
        $result = $this->rankService->getRankList($type_id);
        return success($result);
    }
    
    /**
     * 获取所有档位的排行榜数据
     * @return \think\Response
     */
    public function getAllRankList()
    {
        
        $result = $this->rankService->getAllRankList();
        return success($result);
    }
}