<?php

use think\facade\Route;
use app\middleware\ApiCheckToken;

Route::group( function () {
    Route::post('login', 'User/doLogin');
    Route::get('gifts', 'Lottery/index');
    Route::get('topGifts', 'Lottery/getTopGifts');
    Route::get('allLotteryTypes', 'Lottery/getTypes');
    Route::get('allGiftsByType/:type_id', 'Lottery/getGiftsByType');
    Route::get('rankList/:type_id', 'Rank/getRankList');
    Route::get('allRankList', 'Rank/getAllRankList');

    Route::post('tgCallback', 'TgStar/tgCallback');
});

Route::group( function () {
    Route::get('/me', 'User/getUserInfo');
    Route::get('/myGifts/<type?>', 'User/getUserGifts');
    // Route::get('/myGifts', 'User/getUserGifts');
    Route::get('gifts', 'Lottery/index');
    Route::get('giftAnimation', 'User/getGiftAnimation');
    Route::post('doLottery/:type_id', 'Lottery/doLottery');

    // Route::get('/gift/createInvoiceLink', 'Lottery/createInvoiceLink');
    Route::post('doBuyIntegral/:type_id', 'TgStar/doBuyIntegral');
    Route::get('strIntegralList', 'TgStar/starToIntegrayList');
    Route::post('/checkPayment', 'TgStar/checkPayment');
    Route::post('giftToGift/:id', 'Lottery/giftToGift');
    Route::post('giftToIntegral/:id', 'Lottery/giftToIntegral');
    
})->middleware(ApiCheckToken::class, true);

Route::miss(function () {
    return response("",404);
});