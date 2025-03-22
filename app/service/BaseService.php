<?php

namespace app\service;

use app\validate\Page;
use think\Model;
use think\facade\Lang;
use think\facade\Cache;
use \TelegramBot\Api\BotApi;

/**
 * 基础服务层
 * Class BaseService
 * @package app\service
 */
abstract class BaseService
{
    /**
     * Model 实例
     * @var Model
     */
    protected $model;
    protected $request;
    protected $user_id;
    protected $telegram_id;
    protected $lang;
    protected $telegram;



    public function __construct()
    {
        $this->request = request();
        $this->user_id = $this->request->userId();
        $this->telegram_id = $this->request->telegramId();
        $this->lang = Lang::getLangSet();
    }
    /**
     * 分页列表参数(页码和每页多少条)
     * @return mixed
     */
    public function getPageParam(){

        $page = request()->params([
            ['page', 1],
            ['limit', 15]
        ]);
        validate(Page::class)
            ->check($page);
        return $page;
    }

    /**
     * 分页列表
     * @param array $where
     * @param string $field
     * @param string $order
     * @param int $page
     * @param int $limit
     * @param mixed $with
     * @param mixed $each
     * @return mixed
     */
    public function getPageList(Model $model, array $where, string $field = '*', string $order = '', array $append = [], $with = null, $each = null){
        $page_params = $this->getPageParam();
        $page = $page_params['page'];
        $limit = $page_params['limit'];

        $list = $model->where($where)->when($append, function($query) use ($append){
            $query->append($append);
        })->when($with, function ($query) use($with){
            $query->with($with);
        })->field($field)->order($order)->paginate([
            'list_rows' => $limit,
            'page' => $page,
        ]);
        if(!empty($each)){
            $list = $list->each($each);
        }
        return $list->toArray();
    }

    /**
     * 分页数据查询，传入model（查询后结果）
     * @param $model BaseModel
     * @param $each
     * @return mixed
     */
    public function pageQuery($model, $each = null)
    {
        $page_params = $this->getPageParam();
        $page = $page_params['page'];
        $limit = $page_params['limit'];
        $list = $model->paginate([
            'list_rows' => $limit,
            'page' => $page,
        ]);
        if(!empty($each)){
            $list = $list->each($each);
        }
        return $list->toArray();
    }

    /**
     * 分页视图列表查询
     * @param Model $model
     * @param array $where
     * @param string $field
     * @param string $order
     * @param array $append
     * @param mixed $with
     * @param mixed $each
     * @return array
     * @throws \think\db\exception\DbException
     */
    public function getPageViewList(Model $model, array $where, string $field = '*', string $order = '', array $append = [], $with = null, $each = null){
        $page_params = $this->getPageParam();
        $page = $page_params['page'];
        $limit = $page_params['limit'];

        $list = $model->where($where)->when($append, function($query) use ($append){
            $query->append($append);
        })->when($with, function ($query) use($with){
            $query->withJoin($with);
        })->field($field)->order($order)->paginate([
            'list_rows' => $limit,
            'page' => $page,
        ]);
        if(!empty($each)){
            $list = $list->each($each);
        }
        return $list->toArray();
    }

    /**
     * 通用缓存方法
     * @param string $key 缓存键名
     * @param callable $callback 回调函数，用于获取数据
     * @param int $expire 过期时间（秒），默认1小时
     * @param string $tag 缓存标签，用于批量删除
     * @return mixed
     */
    protected function getCache(string $key, callable $callback, int $expire = 3600, string $tag = '')
    {
        // 尝试从缓存获取数据
        $data = Cache::get($key);
        
        // 如果缓存中没有数据，则执行回调函数获取数据并缓存
        if ($data === null) {
            $data = call_user_func($callback);
            
            // 如果有标签，则使用标签缓存
            if (!empty($tag)) {
                Cache::tag($tag)->set($key, $data, $expire);
            } else {
                Cache::set($key, $data, $expire);
            }
        }
        
        return $data;
    }
    
    /**
     * 删除指定键的缓存
     * @param string $key 缓存键名
     * @return bool
     */
    protected function deleteCache(string $key)
    {
        return Cache::delete($key);
    }
    
    /**
     * 删除指定标签的所有缓存
     * @param string $tag 缓存标签
     * @return bool
     */
    protected function clearCacheByTag(string $tag)
    {
        return Cache::tag($tag)->clear();
    }

    /**
     * 获取Redis锁
     * @param string $key 锁的键名
     * @param int $expire 锁的过期时间（秒）
     * @return bool 是否成功获取锁
     */
    protected function getLock(string $key, int $expire = 10): bool
    {
        // 使用Redis的setnx命令尝试获取锁
        $lockKey = 'lock:' . $key;
        $redis = Cache::store('redis')->handler();
        $result = $redis->set($lockKey, time(), ['NX', 'EX' => $expire]);
        return $result ? true : false;
    }
    
    /**
     * 释放Redis锁
     * @param string $key 锁的键名
     * @return bool 是否成功释放锁
     */
    protected function releaseLock(string $key): bool
    {
        $lockKey = 'lock:' . $key;
        return Cache::store('redis')->delete($lockKey);
    }
}