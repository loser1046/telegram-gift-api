<?php
namespace app;

use think\db\exception\DataNotFoundException;
use think\db\exception\ModelNotFoundException;
use think\exception\Handle;
use think\exception\HttpException;
use think\exception\HttpResponseException;
use think\exception\ValidateException;
use think\Response;
use Throwable;
use think\db\exception\DbException;
use UnexpectedValueException;
use app\exception\AuthException;
use app\exception\ServerException;

/**
 * 应用异常处理类
 */
class ExceptionHandle extends Handle
{
    /**
     * 不需要记录信息（日志）的异常类列表
     * @var array
     */
    protected $ignoreReport = [
        HttpException::class,
        HttpResponseException::class,
        ModelNotFoundException::class,
        DataNotFoundException::class,
        ValidateException::class,
    ];

    /**
     * 记录异常信息（包括日志或者其它方式记录）
     *
     * @access public
     * @param  Throwable $exception
     * @return void
     */
    public function report(Throwable $exception): void
    {
        // 使用内置的方式记录异常日志
        parent::report($exception);
    }

    /**
     * Render an exception into an HTTP response.
     *
     * @access public
     * @param \think\Request   $request
     * @param Throwable $e
     * @return Response
     */
    public function render($request, Throwable $e): Response
    {
        // 添加自定义异常处理机制
        // 添加自定义异常处理机制
        $massageData = env('app_debug', false) ? [
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'message' => $e->getMessage(),
            'trace' => $e->getTrace(),
            'previous' => $e->getPrevious(),
        ] : [];
        // 添加自定义异常处理机制

        if ($e instanceof DbException) {
            return fail($e->getMessage(), []);
        } elseif ($e instanceof ValidateException) {
            return fail($e->getMessage(), []);
        } else if ($e instanceof UnexpectedValueException) {
            return fail($e->getMessage(), [], 401);
        }  else if ($e instanceof ServerException) {
            return fail($e->getMessage(), [], http_code: $e->getCode());
        } else {
            return fail($e->getMessage(), $massageData, code:$e->getCode());
        }
        // 其他错误交给系统处理
        // return parent::render($request, $e);
    }
}
