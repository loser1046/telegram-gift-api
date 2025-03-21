<?php

namespace app\middleware;

use app\Request;
use app\service\UserService;
use Closure;
use Exception;

/**
 * 用户登录token验证
 * Class ApiCheckToken
 * @package app\middleware
 */
class ApiCheckToken
{
    /**
     * @param Request $request
     * @param Closure $next
     * @param bool $is_throw_exception 是否把错误抛出
     * @return mixed
     * @throws Exception
     */
    public function handle(Request $request, Closure $next, bool $is_throw_exception = false)
    {
        try {
            $token = $request->apiToken();
            $token_info = ( new UserService() )->parseToken($token);
        } catch (Exception $e) {
            //是否将登录错误抛出
            if ($is_throw_exception)
                return fail($e->getMessage(), [], $e->getCode());
        }
        if (!empty($token_info)) {
            $request->userId($token_info[ 'user_id' ]);
            // $request->username($token_info[ 'nick_name' ]);
            $request->telegramId($token_info[ 'tg_id' ]);
        }
        return $next($request);
    }
}
