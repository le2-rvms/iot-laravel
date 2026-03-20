<?php

use App\Http\Controllers\DashboardController;
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
    Route::get('/dashboard', DashboardController::class)->name('dashboard');

    Route::resource('users', UserController::class)
        ->except(['show']);

    Route::resource('roles', RoleController::class)
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
