<?php

namespace app\controller;

use app\BaseController;
use \TelegramBot\Api\BotApi;
use \Godruoyi\Snowflake\Snowflake;

class Gift extends BaseController
{

    protected $telegram;

    public function __construct()
    {
        parent::__construct(app());
        $this->telegram = new BotApi(env('telegram.token'),'2200069667:AAG3NXkZiF3ms75TfDeYbUewwExMC8lN0V8');
    }
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $gifts = [];
        // $snowflake = new Snowflake;
        $results = [
            'list_100_percent' => $gifts,
            'list_tg_star_25' => $gifts,
            'list_tg_star_50' => $gifts,
            'list_tg_star_100' => $gifts,
            'buttons' => [
                'play_star_25' => [
                    'item_tile' => 'gift001',
                    'item_description' => 'This is gift001',
                    'star_amount' => 10,
                    'USD_amount' => 10 * 0.018,
                    'invoice_link' => 'https://t.me/$9b3VN_eEoEoBAAAAe90y-N2mzNo',
                    'pay_status' => 0, // 支付状态 0-未支付;1-已支付
                    'transactionId' => 111,
                ],
                'play_star_50' => [
                    'item_tile' => 'gift002',
                    'item_description' => 'This is gift002',
                    'star_amount' => 20,
                    'USD_amount' => 20 * 0.018,
                    'invoice_link' => 'https://t.me/$6-ow3PeEqEoDAAAARFiJUmMBo_o',
                    'pay_status' => 0, // 支付状态 0-未支付;1-已支付
                    'transactionId' => 222,
                ],
                'play_star_100' => [
                    'item_tile' => 'gift003',
                    'item_description' => 'This is gift003',
                    'star_amount' => 30,
                    'USD_amount' => 30 * 0.018,
                    'invoice_link' => 'https://t.me/$Gefgc_eEqEoEAAAA14wrXaHFglU',
                    'pay_status' => 0, // 支付状态 0-未支付;1-已支付
                    'transactionId' => 333,
                ],
            ]

        ];
        return success($results);
    }

    public function createInvoiceLink()
    {
        $invoicelink = $this->telegram->call("createInvoiceLink", data: [
            'title' => 'gift003',
            'description' => 'This is gift003',
            'payload' => json_encode([
                "transactionId" => 333,
                "item_id" => 3,
            ],JSON_UNESCAPED_UNICODE),
            'currency' => "XTR",
            'prices' => json_encode([[
                'label' => 'price',
                'amount' => 30,
                ]])
            ], timeout:10, test:true);
        return success($invoicelink);
    }
}
