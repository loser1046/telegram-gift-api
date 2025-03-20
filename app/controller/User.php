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

    public function getGiftFile()
    {
        $filePath = public_path().'static/stickers/file_0.tgs';  // 替换为实际文件路径
        $fileName = 'file.tgs';               // 替换为实际文件名
        return Response::create($filePath, 'file')->header(['Content-Type'=>'application/octet-stream','Content-Disposition'=>'inline; filename="'. $fileName. '"'] )->cacheControl('public, max-age=86400');

        // 使用sendfile方法发送文件
        // return response()->sendfile($filePath, 'application/octet-stream', [
        //     'Content-Disposition' => 'inline; filename="' . $fileName . '"'
        // ]);
        // $gift_animation = $this->telegram->getFile($gift_tg_id);
        // return download(public_path().'static/stickers/file_0.tgs', 'file_0.tgs')->force(false);
    }

}