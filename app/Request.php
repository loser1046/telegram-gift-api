<?php

namespace app;

/**
 * Class Request
 * @package app
 */
// 应用请求对象类
class Request extends \think\Request
{
    //认证信息
    protected static $auth_info = [];

    /**
     * 获取请求参数
     * @param array $params
     * @param bool $filter
     * @return array
     */
    public function params(array $params, bool $filter = true): array
    {
        $input = [];
//        $filter_rule = $filter ? 'strip_tags' : '';
        $filter_rule = '';
        foreach ($params as $param) {
            $key = $param[0];
            $default = $param[1];
            $item_filter = $param[2] ?? $filter;
            $input[$key] = $this->paramFilter($this->param($key, $default, $filter_rule ?? ''), $item_filter);
            //过滤后产生空字符串，按照默认值
            if($input[$key] === '')
            {
                $input[$key] = $default;
            }
        }
        return $input;
    }

    /**
     * 参数过滤
     * @param $param
     * @param bool $filter
     * @return array|string|string[]|null
     */
    public function paramFilter($param, bool $filter = true)
    {
        if (!$param || !$filter || !is_string($param)) return $param;
        // 把数据过滤
        $filter_rule = [
            "/<(\\/?)(script|i?frame|style|html|body|title|link|meta|object|\\?|\\%)([^>]*?)>/isU",
            "/(<[^>]*)on[a-zA-Z]+\s*=([^>]*>)/isU",
            "/select|join|where|drop|like|modify|rename|insert|update|table|database|alter|truncate|\'|\/\*|\.\.\/|\.\/|union|into|load_file|outfile/is"
        ];
        $param = preg_replace($filter_rule, '', $param);
        return $param;
    }

    /**
     * 获取登录用户的telegramid
     * @param $params
     * @return int|bool
     */
    public function telegramId(int $telegram_id = 0)
    {
        if ($telegram_id > 0) {
            static::$auth_info['telegram_id'] = $telegram_id;
        } else {
            return static::$auth_info['telegram_id'] ?? 0;
        }
        return true;
    }


    /**
     * 获取登录用户的id
     * @param $params
     * @return int|bool
     */
    public function userId(int $user_id = 0)
    {
        if ($user_id > 0) {
            static::$auth_info['user_id'] = $user_id;
        } else {
            return static::$auth_info['user_id'] ?? 0;
        }
        return true;
    }

    /**
     * 用户账号
     * @param string $username
     * @return int|mixed
     */
    public function username(string $username = '')
    {
        if (!empty($username)) {
            static::$auth_info['username'] = $username;
        } else {
            return static::$auth_info['username'] ?? '';
        }
        return true;
    }

    /**
     * 获取用户token
     * @return array|string|null
     */
    public function apiToken(){
        return $this->header('token');
    }

    /**
     * get传参追加值
     * @param $data
     * @return void
     */
    public function pushGet($data){
        $param = $this->get();
        $this->withGet(array_merge($param, $data));
    }

    /**
     * header传参追加值
     * @param $data
     * @return void
     */
    public function pushHeader($data){
        $param = $this->header();
        $this->withHeader(array_merge($param, $data));
    }
}
