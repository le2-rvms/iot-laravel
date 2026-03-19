<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Roles\RoleController;
use App\Http\Controllers\Settings\SettingsController;
use App\Http\Controllers\Users\UserController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return Auth::check() ? to_route('dashboard') : to_route('login');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', DashboardController::class)
        ->middleware('can:dashboard.read')
        ->name('dashboard');

    Route::resource('users', UserController::class)
        ->only(['index'])
        ->middleware('can:users.read');

    Route::resource('users', UserController::class)
        ->except(['index', 'show'])
        ->middleware('can:users.write');

    Route::resource('roles', RoleController::class)
        ->only(['index'])
        ->middleware('can:roles.read');

    Route::resource('roles', RoleController::class)
        ->except(['index', 'show'])
        ->middleware('can:roles.write');

    Route::get('/settings', SettingsController::class)
        ->middleware('can:settings.read')
        ->name('settings.index');
});
