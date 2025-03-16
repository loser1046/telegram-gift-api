<?php

namespace app\controller;

use app\BaseController;
use Longman\TelegramBot\Telegram;
use think\facade\Log;
use \TelegramBot\Api\BotApi;


class TgStar extends BaseController
{

    protected $telegram;

    public function __construct()
    {
        parent::__construct(app());
        $this->telegram = new BotApi(env('telegram.token'),'2200069667:AAG3NXkZiF3ms75TfDeYbUewwExMC8lN0V8');
    }

    public function checkPayment()
    {
        // $user = auth()->user();

        // $record = new TgStarTransaction;
        // $record->tg_id = $user->tg_id;
        // $record->gift_id = $request->gift_id;
        // $record->transaction_id = $request->transaction_id;
        // $record->status = 1; // TODO: 0
        // $record->amount = 1; // TODO

        // $record->save();
        $record = [];

        return success($record);
    }

    public function tgCallback()
    {
        $headers = $this->request->header();
        $data = $this->request->post();
        if (empty($headers) || empty($data)) {
            return fail('Invalid request');
        }
        $logs = json_encode([
            'headers' => $headers,
            'data' => $data,
        ]);
        Log::debug($logs);

        if(!isset($headers['x-telegram-bot-api-secret-token']) || $headers['x-telegram-bot-api-secret-token'] !== env('telegram.secret_token','secret_token_string')){
            return fail('Invalid secret token');
        }

        if (isset($data['pre_checkout_query'])) {
            $this->handlePreCheckout($data['pre_checkout_query']);
        } elseif (isset($data['message']['successful_payment'])) {
            $this->handleSuccessfulPayment($data['message']['successful_payment']);
        }
    }

    private function handlePreCheckout($preCheckoutQuery)
    {
        $orderId = $preCheckoutQuery['invoice_payload'];
        // $order = Order::where('order_id', $orderId)->find();
        $order = [
            'status' => 'pending',
            // 'status' => 'over',
        ];

        if ($order && $order['status'] === 'pending') {
            $this->telegram->answerPreCheckoutQuery($preCheckoutQuery['id'],true);
        } else {
            $this->telegram->answerPreCheckoutQuery($preCheckoutQuery['id'],false,'Invalid order');
        }
    }

    private function handleSuccessfulPayment($successfulPayment)
    {
        $orderId = $successfulPayment['invoice_payload'];
        // $order = Order::where('order_id', $orderId)->find();
        $order = [
            'status' => 'pending',
            'user_id' => 2200607499,
            'gift_id' => '5453972608896729089',
        ];

        if ($order && $order['status'] === 'pending') {
            // $order->status = 'paid';
            // $order->save();

            // 抽奖逻辑
            // $product = Db::table('products')->where('id', $order['product_id'])->find();
            // $prize = $this->lottery($product['probability']);
            // $order->prize_info = $prize;
            // $order->save();
            $this->sendGiftToUser($order['user_id'], $order['gift_id']);
        }
    }

    /**
     * 发送礼物给用户
     */
    private function sendGiftToUser($userId, $giftId)
    {
        try {
            // 调用Telegram Bot API的sendGift方法
            $this->telegram->call("sendGift",[
                'user_id' => $userId, // 用户的Telegram ID
                'gift_id' => $giftId, // 指定的礼物ID
            ]);
        } catch (\Exception $e) {
            // 处理发送失败的情况，例如记录错误日志
            Log::error('发送礼物失败: ' . $e->getMessage());
        }
    }
}
