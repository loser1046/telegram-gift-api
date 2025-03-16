<?php
// 应用公共文件

use think\Response;

/**
 * 接口操作成功，返回信息
 * @param int|string|array $msg
 * @param mixed $data
 * @param int $code
 * @param int $http_code
 */
function success($msg = 'SUCCESS', $data = [], int $code = 1, int $http_code = 200): Response
{
	if (is_array($msg)) {
		$data = $msg;
		$msg = 'SUCCESS';
	}
	return Response::create(['data' => $data, 'msg' => $msg, 'code' => $code], 'json', $http_code);

}

/**
 * 接口操作失败，返回信息
 */
function fail($msg = 'FAIL', ?array $data = [], int $code = 0, int $http_code = 200): Response
{
	if (is_array($msg)) {
		$data = $msg;
		$msg = 'FAIL';
	}
	return Response::create(['data' => $data, 'msg' => $msg, 'code' => $code], 'json', $http_code);
}


/**
 * Generate a "random" alpha-numeric string.
 *
 * Should not be considered sufficient for cryptography, etc.
 *
 * @param  int  $length
 * @return string
 */
function quickRandom($length = 12)
{
	$pool = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';

	return substr(str_shuffle(str_repeat($pool, $length)), 0, $length);
}