<?php

use App\Http\Controllers\Api\Mqtt\EmqxAuthController;
use Illuminate\Support\Facades\Route;

// EMQX 鉴权是机器到机器接口，单独走 API 路由和限流，不进入后台会话链。
Route::post('/emqx/auth', [EmqxAuthController::class, 'authenticate'])
    ->middleware('throttle:emqx-auth');
