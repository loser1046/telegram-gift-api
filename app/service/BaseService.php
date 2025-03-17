<?php

namespace app\service;

use app\validate\Page;
use think\Model;
use think\facade\Lang;
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

}