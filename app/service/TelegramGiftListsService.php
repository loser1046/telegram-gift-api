<?php
declare(strict_types=1);

namespace app\service;

use app\model\TelegramGiftLists;
use \TelegramBot\Api\BotApi;

class TelegramGiftListsService extends BaseService
{

    public function __construct()
    {
        parent::__construct();
        $this->telegram = new BotApi(env('telegram.token'));
        $this->model = new TelegramGiftLists();
    }

    /**
     * 同步telegram礼物列表
     * @return mixed
     */
    public function telegramGiftSyn(): mixed
    {
        try {
            //同步telegram的礼物列表
            $lists = $this->telegram->call("getAvailableGifts");
            if (empty($lists) || !isset($lists['gifts']) || empty($lists['gifts'])) {
                return false;
            }
            $new_gift_list = [];
            foreach ($lists['gifts'] as $value) {
                $new_gift_list[] = [
                    'gift_tg_id' => $value['id'],
                    'emoji' => $value['sticker']['emoji'] ?? '',
                    'is_animated' => $value['sticker']['is_animated'] ? 1 : 0,
                    'is_video' => $value['sticker']['is_video'] ? 1 : 0,
                    'type' => $value['sticker']['type'] ?? '',
                    'custom_emoji_id' => $value['sticker']['custom_emoji_id'] ?? '',
                    'file_id' => $value['sticker']['file_id'] ?? '',
                    'file_unique_id' => $value['sticker']['file_unique_id'] ?? '',
                    'is_limit' => isset($value['remaining_count']) ? 1 : 0,
                    'star_count' => $value['star_count'] ?? 0,
                    'upgrade_star_count' => $value['upgrade_star_count'] ?? 0,
                    'remaining_count' => $value['remaining_count'] ?? 0,
                    'total_count' => $value['total_count'] ?? 0,
                    'is_del' => 0,
                    'create_time' => time(),
                    'update_time' => time(),
                ];
            }
            $now_lists = $this->model->select()->toArray();
            if (empty($now_lists)) {
                $this->model->insertAll($new_gift_list);
            }
        } catch (\Exception $e) {
            var_dump($e->getMessage());
        }
        return $lists['gifts'];
    }

    public function telegramGiftAnimationSyn($gifts)
    {
        try {
            $stickersDir = public_path() . 'static/stickers/';
            $jsonDir = public_path() . 'static/json/';
            if (!is_dir($stickersDir)) {
                mkdir($stickersDir, 0755, true);
            }
            if (!is_dir($jsonDir)) {
                mkdir($jsonDir, 0755, true);
            }
            
            foreach ($gifts as $value) {
                $file_id = $value['sticker']['file_id'];
                $animation = $this->telegram->downloadFile($file_id);
                $tgsFilePath = $stickersDir . $value['id'] . '.tgs';
                $jsonFilePath = $jsonDir . $value['id'] . '.json';

                // 保存tgs文件
                file_put_contents($tgsFilePath, $animation);
                //  解析tgs文件为JSON tgs文件是gzip压缩的JSON，需要解压
                $decompressed = $this->decompressTgsToJson($animation);
                if ($decompressed) {
                    file_put_contents($jsonFilePath, $decompressed);
                }
            }
        } catch (\Exception $e) {
            var_dump($e->getMessage());
        }
    }
    
    /**
     * 将tgs格式解压为JSON
     * @param string $tgsData tgs二进制数据
     * @return string|false 解压后的JSON字符串，失败返回false
     */
    private function decompressTgsToJson(string $tgsData)
    {
        try {
            $decompressed = gzdecode($tgsData);
            if ($decompressed === false) {
                return false;
            }
            
            // 验证是否有效的JSON
            $json = json_decode($decompressed);
            if (json_last_error() !== JSON_ERROR_NONE) {
                return false;
            }
            return $decompressed;
            // return json_encode($json, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        } catch (\Exception $e) {
            var_dump('解压tgs文件失败: ' . $e->getMessage());
            return false;
        }
    }
}
