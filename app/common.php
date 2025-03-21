<?php
// 应用公共文件

use think\facade\Log;
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

function getGiftAnimationString($gift_tg_id)
{
	$json_file_path = public_path() . 'static/json/' . $gift_tg_id . '.json';
	if (file_exists($json_file_path)) {
		$content = file_get_contents($json_file_path);
		// 检查是否为有效的UTF-8编码
		if (!mb_check_encoding($content, 'UTF-8')) {
			// 尝试修复编码问题
			$content = mb_convert_encoding($content, 'UTF-8', 'auto');
			// 如果仍然无法转换为有效的UTF-8，返回空字符串
			if (!mb_check_encoding($content, 'UTF-8')) {
				Log::error('【文件编码有误】: ' . $gift_tg_id);
				return "";
			}
		}
		// 验证是否为有效的JSON
		// json_decode($content);
		// if (json_last_error() !== JSON_ERROR_NONE) {
		// 	Log::error('Invalid JSON in gift animation file: ' . $gift_tg_id . ', Error: ' . json_last_error_msg());
		// 	return "";
		// }
		return $content;
	}
	return "";
}

function getGiftAnimationTgs($gift_tg_id)
{
	$tgs_file_path = public_path() . 'static/stickers/' . $gift_tg_id . '.tgs';
	$fileName = $gift_tg_id . '.tgs';               // 替换为实际文件名

	if (file_exists($tgs_file_path)) {
		return Response::create($tgs_file_path, 'file')->header(['Content-Type' => 'application/octet-stream', 'Content-Disposition' => 'inline; filename="' . $fileName . '"'])->cacheControl('public, max-age=86400');
		// $content = file_get_contents($tgs_file_path);

		// return Response::create($tgs_file_path, 'file')
		// 	->header(['Content-Type' => 'application/x-tgsticker'])
		// 	->cacheControl('public, max-age=86400');
	} else {
		return fail('File not found', http_code: 404);
	}
}


function validateTelegramHash($botToken, $telegramInitData)
{
	// 解析参数并保留原始编码值
	$params = explode('&', $telegramInitData);
	$data = [];
	foreach ($params as $param) {
		$pair = explode('=', $param, 2);
		$key = urldecode($pair[0]);
		$value = $pair[1] ?? '';
		$data[$key] = $value;
	}

	if (!env('APP_DEBUG') && isset($data['auth_date']) && time() - $data['auth_date'] > 300) {
		return false;
	}

	// 提取并移除hash参数
	if (!isset($data['hash'])) {
		return false;
	}
	$hash = $data['hash'];
	unset($data['hash']);
	// 按键名排序
	ksort($data);
	// 构造数据校验字符串
	$dataCheckStrings = [];
	foreach ($data as $k => $v) {
		$dataCheckStrings[] = "{$k}={$v}";
	}
	$dataCheckString = implode("\n", $dataCheckStrings);
	// 生成签名密钥
	$secretKey = hash_hmac('sha256', $botToken, 'WebAppData', true);
	// 计算哈希值
	$calculatedHash = hash_hmac('sha256', $dataCheckString, $secretKey);
	// 安全比较哈希值
	return hash_equals($calculatedHash, $hash);
}