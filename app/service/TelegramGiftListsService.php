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

    public function telegramGiftSyn()
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
                    'is_limit' => isset($value['sticker']['custom_emoji_id']) ? 1 : 0,
                    'star_count' => $value['star_count'] ?? 0,
                    'upgrade_star_count' => $value['upgrade_star_count'] ?? 0,
                    'remaining_count' => $value['remaining_count'] ?? 0,
                    'total_count' => $value['total_count'] ?? 0,
                    'is_del' => 0,
                ];
            }
            $now_lists = $this->model->select()->toArray();
            if (empty($now_lists)) {
                $this->model->insertAll($new_gift_list);
            }
        } catch (\Exception $e) {
            var_dump($e->getMessage());
        }

    }
}
