<?php

use think\facade\Route;
use app\middleware\ApiCheckToken;

Route::group( function () {
    Route::post('login', 'User/doLogin');
    Route::get('gifts', 'Gifts/index');
    Route::get('topGifts', 'Gifts/getTopGifts');
    Route::get('allGiftTypes', 'Gifts/getTypes');
    Route::get('allGiftsByType/:type_id', 'Gifts/getGiftsByType');
    Route::get('rankList/:type_id', 'Rank/getRankList');
    Route::get('allRankList', 'Rank/getAllRankList');

    Route::post('tgCallback', 'TgStar/tgCallback');
});

Route::group( function () {
    Route::get('/me', 'User/getUserInfo');
    Route::get('/myGifts', 'User/getUserGifts');
    Route::get('gifts', 'Gifts/index');
    Route::post('doLottery/:type_id', 'Gifts/doLottery');

    Route::get('/gift/createInvoiceLink', 'Gifts/createInvoiceLink');
    Route::post('/checkPayment', 'TgStar/checkPayment');
    
})->middleware(ApiCheckToken::class, true);

Route::miss(function () {
    return response("",404);
});