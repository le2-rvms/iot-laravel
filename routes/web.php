<?php

use App\Http\Middleware\AuthorizeControllerPermission;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Roles\RoleController;
use App\Http\Controllers\Settings\FormLabController;
use App\Http\Controllers\Settings\SettingsController;
use App\Http\Controllers\Users\UserController;
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

    Route::get('/settings', SettingsController::class)->name('settings.index');

    Route::get('/settings/form-lab', [FormLabController::class, 'create'])->name('settings.form-lab');

    Route::post('/settings/form-lab', [FormLabController::class, 'store'])->name('settings.form-lab.store');
});
