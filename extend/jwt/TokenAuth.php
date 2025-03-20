<?php

namespace jwt;

use Firebase\JWT\JWT;
use think\facade\Cache;
use Firebase\JWT\Key;

/**
 * token工具类
 * Class TokenAuth
 * @package core\util
 */
class TokenAuth
{
    /**
     *创建token
     * @param int|string $user_id int  用户id
     * @param string $app_type 类型（api）
     * @param array $payload 参数  用户信息
     * @param int $expire_time 有效期
     * @return array
     */
    public static function createToken(int $user_id, string $app_type = "api", array $payload = [], int $expire_time = 3600): array
    {
        $time = time();
        $token_payload = [
            'iss' => config('jwt.iss', 'telegram-gift-api'),  // 签发者
            'aud' => config('jwt.aud', 'telegram-users'),     // 接收者
            'iat' => $time,                                   // 签发时间
            'nbf' => $time,                                   // 生效时间
            'exp' => $time + $expire_time,                    // 过期时间
            'uid' => $user_id,                                // 用户ID
            'app' => $app_type,                               // 应用类型
            'data' => $payload                                // 自定义数据
        ];

        $token_payload['jti'] = (string) $user_id . "_" . $app_type;
        $token = JWT::encode($token_payload, config('jwt.secret_key', 'TelegramGiftBotApi$%^'), 'HS256');
        // 如果 token 有效，延长缓存时间7天
        Cache::tag("token")->set("token_" . $token_payload['jti'], $token, $expire_time);

        return [
            'token' => $token,
            'expire_time' => $time + $expire_time
        ];
    }

    /**
     * 解析token
     * @param string $token
     * @return array
     */
    public static function parseToken(string $token): array
    {
        try {
            $payload = JWT::decode($token, new Key(config('jwt.secret_key'), 'HS256'));
            if (!empty($payload)) {
                $token_info = json_decode(json_encode($payload), true);
                // 检查 token 是否在缓存中
                $cached_tokens = Cache::get('token_' . $token_info['jti']);
                if (!$cached_tokens) {
                    return [];
                }
                return $token_info;
            } else {
                return [];
            }
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * 清理token
     * @param int $user_id 用户ID
     * @param string $app_type 应用类型
     * @param string|null $token 指定要清除的token
     * @return array
     */
    public static function clearToken(int $user_id, string $app_type = "api", ?string $token = ''): bool
    {
        $cache_key = "token_" . (string)$user_id . "_" . $app_type;
        
        // 如果提供了特定token，验证后再删除
        if (!empty($token)) {
            $cached_token = Cache::get($cache_key);
            // 只有当缓存中的token与提供的token匹配时才删除
            if ($cached_token === $token) {
                Cache::delete($cache_key);
                return true;
            }
            return false;
        } else {
            // 直接清除该用户的token缓存
            Cache::delete($cache_key);
            return true;
        }
    }
}
