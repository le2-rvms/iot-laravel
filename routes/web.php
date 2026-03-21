<?php

use App\Http\Controllers\Account\PasswordController as AccountPasswordController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\MqttAccounts\MqttAccountController;
use App\Http\Controllers\Roles\RoleController;
use App\Http\Controllers\Settings\SettingsApplicationConfigController;
use App\Http\Controllers\Settings\SettingsPrecognitionController;
use App\Http\Controllers\Settings\SettingsSystemConfigController;
use App\Http\Controllers\Settings\SettingsVeeValidateController;
use App\Http\Controllers\Users\UserController;
use App\Http\Middleware\AuthorizeControllerPermission;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return Auth::check() ? to_route('dashboard') : to_route('login');
});

Route::middleware(['auth', 'verified', AuthorizeControllerPermission::class])->group(function () {
    // 避免与 Fortify 已存在的 password.update 路由名冲突。
    Route::singleton('account/security-password', AccountPasswordController::class)
        ->only(['edit', 'update']);

    Route::get('/dashboard', DashboardController::class)->name('dashboard');

    Route::resource('users', UserController::class)
        ->except(['show']);

    Route::resource('roles', RoleController::class)
        ->except(['show']);

    // MQTT 账号走标准资源路由，保持和用户/角色/配置页相同的后台维护结构。
    Route::resource('mqtt-accounts', MqttAccountController::class)
        ->except(['show']);

    Route::resource('settings/application-configs', SettingsApplicationConfigController::class)
        ->parameters(['application-configs' => 'config'])
        ->except(['show']);

    Route::resource('settings/system-configs', SettingsSystemConfigController::class)
        ->parameters(['system-configs' => 'config'])
        ->except(['show']);

    Route::resource('settings/vee-validate', SettingsVeeValidateController::class)
        ->only(['index', 'store']);

    Route::resource('settings/precognition', SettingsPrecognitionController::class)
        ->only(['index', 'store']);
});
