<?php

use App\Http\Controllers\Web\Admin\Account\PasswordController as AccountPasswordController;
use App\Http\Controllers\Web\Admin\Admin\AdminRoleController;
use App\Http\Controllers\Web\Admin\Admin\AdminUserController;
use App\Http\Controllers\Web\Admin\Audits\AuditController;
use App\Http\Controllers\Web\Admin\ClientMonitor\ClientMonitorController;
use App\Http\Controllers\Web\Admin\Devices\DeviceController;
use App\Http\Controllers\Web\Admin\DeviceProducts\DeviceProductController;
use App\Http\Controllers\Web\Admin\DashboardController;
use App\Http\Controllers\Web\Admin\MqttAccounts\MqttAccountController;
use App\Http\Controllers\Web\Admin\Settings\SettingsApplicationConfigController;
use App\Http\Controllers\Web\Admin\Settings\SettingsPrecognitionController;
use App\Http\Controllers\Web\Admin\Settings\SettingsSystemConfigController;
use App\Http\Controllers\Web\Admin\Settings\SettingsVeeValidateController;
use App\Http\Controllers\Web\Auth\DevQuickLoginController;
use App\Http\Middleware\AuthorizeControllerPermission;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return Auth::check()
        ? redirect()->route('dashboard')
        : redirect()->route('login');
});

if (app()->environment(['local','dev'])) {
    Route::middleware('guest')
        ->post('login/dev-users/{adminUser}', DevQuickLoginController::class)
        ->name('dev-users.login');
}

Route::prefix('admin')->middleware(['auth', 'verified', AuthorizeControllerPermission::class])->group(function () {
    // 避免与 Fortify 已存在的 password.update 路由名冲突。
    Route::singleton('account/security-password', AccountPasswordController::class)
        ->only(['edit', 'update']);

    Route::get('dashboard', DashboardController::class)->name('dashboard');
    Route::get('admin-users/export', [AdminUserController::class, 'export'])->name('admin-users.export');
    Route::resource('admin-users', AdminUserController::class)
        ->except(['show']);

    Route::get('admin-roles/export', [AdminRoleController::class, 'export'])->name('admin-roles.export');
    Route::resource('admin-roles', AdminRoleController::class)
        ->except(['show']);

    Route::get('audits/export', [AuditController::class, 'export'])->name('audits.export');
    Route::resource('audits', AuditController::class)
        ->only(['index']);

    Route::get('client-monitor/device-overview', [ClientMonitorController::class, 'index'])
        ->name('client-monitor.device-overview');
    Route::get('client-monitor/sessions', [ClientMonitorController::class, 'sessions'])
        ->name('client-monitor.sessions');
    Route::get('client-monitor/auth-events', [ClientMonitorController::class, 'authEvents'])
        ->name('client-monitor.auth-events');
    Route::get('client-monitor/cmd-events', [ClientMonitorController::class, 'cmdEvents'])
        ->name('client-monitor.cmd-events');
    Route::get('client-monitor/conn-events', [ClientMonitorController::class, 'connEvents'])
        ->name('client-monitor.conn-events');
    Route::get('client-monitor/gps-position-last', [ClientMonitorController::class, 'gpsPositionLast'])
        ->name('client-monitor.gps-position-last');
    Route::get('client-monitor/gps-position-histories', [ClientMonitorController::class, 'gpsPositionHistories'])
        ->name('client-monitor.gps-position-histories');

    // MQTT 账号走标准资源路由，保持和用户/角色/配置页相同的后台维护结构。
    Route::get('mqtt-accounts/export', [MqttAccountController::class, 'export'])->name('mqtt-accounts.export');
    Route::resource('mqtt-accounts', MqttAccountController::class)
        ->except(['show']);

    Route::get('device-products/export', [DeviceProductController::class, 'export'])->name('device-products.export');
    Route::resource('device-products', DeviceProductController::class)
        ->parameters(['device-products' => 'deviceProduct'])
        ->except(['show']);

    Route::get('devices/export', [DeviceController::class, 'export'])->name('devices.export');
    Route::resource('devices', DeviceController::class)
        ->except(['show']);

    Route::get('settings/application-configs/export', [SettingsApplicationConfigController::class, 'export'])
        ->name('application-configs.export');
    Route::resource('settings/application-configs', SettingsApplicationConfigController::class)
        ->parameters(['application-configs' => 'config'])
        ->except(['show']);

    Route::get('settings/system-configs/export', [SettingsSystemConfigController::class, 'export'])
        ->name('system-configs.export');
    Route::resource('settings/system-configs', SettingsSystemConfigController::class)
        ->parameters(['system-configs' => 'config'])
        ->except(['show']);

    Route::resource('settings/vee-validate', SettingsVeeValidateController::class)
        ->only(['index', 'store']);

    Route::resource('settings/precognition', SettingsPrecognitionController::class)
        ->only(['index', 'store']);
});
