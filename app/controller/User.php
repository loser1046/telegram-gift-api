<?php

namespace app\controller;

use app\BaseController;
use app\service\GiftService;
use app\service\LotteryService;
use app\service\TgStarService;
use app\service\UserService;
use think\Response;

class User extends BaseController
{
    /**
     * 登录
     * @return Response
     */
    public function doLogin(): Response
    {
        $data = $this->request->params([
            ['code', ''],
        ]);

        $this->validate($data, 'app\validate\User.login');

        $result = (new UserService())->login($data['code']);
        if (!$result) {
            return fail('The code is wrong');
        }
        return success($result);
    }
    
    /**
     * 获取用户信息
     */
    public function getUserInfo()
    {
        return success((new UserService())->getUserInfo());
    }


    /**
     * 根据条件返回用户的Gifts
     * @return Response
     */
    public function getUserGifts($type = null)
    {
        if ($type == 'limit') {
            $data = (new LotteryService())->getUserGifts(is_limit: true);
        }elseif ($type == 'all') {
            $data = (new LotteryService())->getUserGifts(is_limit: false);
        }else{
            $data = (new LotteryService())->getUserAllGifts();
        }
        return success($data);
    }

    /**
     * 获取用户限量Gifts和所有Gifts
     * @return Response
     */
    public function getUserAllGifts()
    {
        return success((new LotteryService())->getUserAllGifts());
    }

    public function getGiftAnimation($gift_tg_id)
    {
        return success((new GiftService())->getGiftAnimation($gift_tg_id));
    }
}