<?php

namespace app\controller;

use app\BaseController;
use app\service\TgStarService;
use think\facade\Log;
use \TelegramBot\Api\BotApi;


class TgStar extends BaseController
{

    protected $telegram;
    protected $tgStarService;

    public function __construct()
    {
        parent::__construct(app());
        $this->telegram = new BotApi(env('telegram.token'),'2200069667:AAG3NXkZiF3ms75TfDeYbUewwExMC8lN0V8');
        $this->tgStarService = new TgStarService();
    }

    /**
     * 检查抽奖支付&发放状态
     * @return \think\Response
     */
    public function checkPayment()
    {
        $data = $this->request->params([
                ["transaction_id",""]
            ]
        );

        $this->validate($data, 'app\validate\TgStar.checkPayment');

        $record = $this->tgStarService->getRecordByTransactionId($data['transaction_id']);

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

        if (!$this->tgStarService->validateTelegramCallback($headers)) {
            Log::debug("签名验证失败");
            return fail('Invalid secret token');
        }

        if (isset($data['pre_checkout_query'])) {
            $this->tgStarService->handlePreCheckout($data['pre_checkout_query']);
        } elseif (isset($data['message']['successful_payment'])) {
            $this->tgStarService->handleSuccessfulPayment($data['message']['successful_payment']);
        }
        
        return success('Callback processed');
    }


}
