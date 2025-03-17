<?php

namespace app\controller;

use app\BaseController;
use app\service\TgStarService;
use app\service\UserService;
use think\Response;

class User extends BaseController
{
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

    public function getUserGifts()
    {
        $data = $this->request->params([
            ['is_limit', 1]
        ]);
        return success((new TgStarService())->getUserGifts($data['is_limit'] == 1));
    }

}