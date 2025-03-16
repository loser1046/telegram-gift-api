<?php
declare (strict_types = 1);

namespace app\service;

use app\model\Users;
use jwt\TokenAuth;
use think\facade\Log;
use think\Response;

class UserService extends BaseService
{
    public function __construct()
    {
        parent::__construct();
        $this->model = new Users();
    }

    /**
     * 登录/注册
     * @param string $login_code
     * @return array|Response
     */
    public function login($login_code): array|Response
    {
        parse_str($login_code, $output);
        $request_user_data = json_decode(urldecode($output['user']),true);
        if (!$request_user_data["id"]) return fail('tg_id is required!');
        $user = $this->model->where(['tg_id' => $request_user_data["id"]])->findOrEmpty()->toArray();

        if (empty($user)) {
            $user = $this->createNewUser($request_user_data);
        }

        $token = $this->createToken($user);
        return [
            'token' => $token,
            'user' => $user,
        ];
    }

    /**
     * 创建新用户
     * @param array $request_user_data
     * @return array
     */
    private function createNewUser(array $request_user_data): array
    {
        if (empty($request_user_data['first_name']) && empty($request_user_data['last_name'])) {
            $nickname = quickRandom();
        } else if (empty($request_user_data['first_name'])) {
            $nickname = $request_user_data['last_name'];
        } else if (empty($request_user_data['last_name'])) {
            $nickname = $request_user_data['first_name'];
        } else {
            $nickname = $request_user_data['first_name'] . '_' . $request_user_data['last_name'];
        }


        $usersModel = Users::create([
            'tg_id' => $request_user_data['id'] ?? 0,
            'first_name' => $request_user_data['first_name'] ?? '',
            'last_name' => $request_user_data['last_name'] ?? '',
            'nick_name' => $nickname,
            'username' => $request_user_data['username'] ?? '',
            'lang' => $request_user_data['language_code'] ?? '',
            'photo_url' => $request_user_data['photo_url'] ?? '',
            'is_premium' => $request_user_data['is_premium'] ?? 0,
            'allows_write_to_pm' => $request_user_data['allows_write_to_pm'] ? 1 : 0
        ]);
        return $usersModel->toArray();
    }


    /**
     * 生成token
     * @param $user_info
     * @return array
     */
    protected function createToken($user_info): ?array
    {
        $expire_time = config('jwt.api_token_expire_time') ?? 3600;
        $token_info = TokenAuth::createToken($user_info['id'], "api", ['user_id' => $user_info['id'], 'tg_id' => $user_info['tg_id'], 'nick_name' => $user_info['nick_name']], $expire_time);
        return $token_info;
    }

    /**
     * 解析token
     * @param string $token
     * @return array
     * @throws \Exception
     */
    public function parseToken(?string $token): array
    {
        if (empty($token)) {
            Log::error("token 缺少, token:{$token}");
            throw new \Exception('MUST_LOGIN', 401);
        }

        try {
            $token_info = TokenAuth::parseToken($token);
        } catch (\Throwable $e) {
            Log::error("token 解析失败, token:{$token},失败原因：" . $e->getMessage());
            throw new \Exception('LOGIN_EXPIRE', 401);
        }
        if (!$token_info) {
            Log::error("token 解析失败, token_info为空，token:{$token},");
            throw new \Exception('MUST_LOGIN', 401);
        }
        return $token_info['data'];
    }

}
