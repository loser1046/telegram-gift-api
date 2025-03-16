<?php

namespace app\controller;

use app\BaseController;
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

}