<?php

use App\Http\Controllers\BpjsDashboardController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\MaintenanceWindowController;
use App\Http\Controllers\MonitorController;
use App\Http\Controllers\NotificationChannelController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\StatusPageController;
use Illuminate\Support\Facades\Route;

Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
Route::post('/check-all', [DashboardController::class, 'checkAll'])->name('dashboard.check-all');

Route::resource('monitors', MonitorController::class);
Route::post('monitors/{monitor}/check', [MonitorController::class, 'checkNow'])->name('monitors.check-now');
Route::patch('monitors/{monitor}/toggle', [MonitorController::class, 'toggle'])->name('monitors.toggle');

Route::prefix('api-health')->name('api-health.')->group(function () {
    Route::get('/', [BpjsDashboardController::class, 'index'])->name('dashboard');
    Route::post('/check-all', [BpjsDashboardController::class, 'checkAll'])->name('check-all');
    Route::post('/check/{serviceKey}', [BpjsDashboardController::class, 'checkService'])->name('check-service');
    Route::post('/mode/{mode}', [BpjsDashboardController::class, 'switchMode'])->name('switch-mode');
});

Route::resource('channels', NotificationChannelController::class)->except(['show']);

// Push heartbeat endpoint — dipanggil oleh cron/script eksternal
Route::get('/push/{token}', [MonitorController::class, 'receivePush'])->name('monitors.push');
Route::resource('maintenance', MaintenanceWindowController::class)->except(['show']);
Route::resource('status-pages', StatusPageController::class)->except(['show']);
Route::get('/status/{slug}', [StatusPageController::class, 'show'])->name('status.public');

Route::get('/settings', [SettingController::class, 'index'])->name('settings.index');
Route::put('/settings', [SettingController::class, 'update'])->name('settings.update');
