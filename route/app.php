<?php

use think\facade\Route;
use app\middleware\ApiCheckToken;

// Route::group('api/v1', function () {


    Route::group( function () {
        Route::post('login', 'User/doLogin');
        Route::get('gifts', 'Gift/index');

        Route::post('tgCallback', 'TgStar/tgCallback');
    });

    Route::group( function () {
        // Route::post('/logout', [UserController::class, 'logout']);
        // Route::post('/refresh', [UserController::class, 'refresh']);
        // Route::get('/me', [UserController::class, 'me']);
        Route::get('gifts', 'Gift/index');
    
        Route::get('/gift/createInvoiceLink', 'Gift/createInvoiceLink');
        Route::post('/tgStar/checkPayment', 'TgStar/checkPayment');
    })->middleware(ApiCheckToken::class, true);
// });


Route::miss(function () {
    return response("",404);
});