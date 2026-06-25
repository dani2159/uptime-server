<?php

use App\Http\Controllers\AuditLogController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\EscalationController;
use App\Http\Controllers\TagController;
use App\Http\Controllers\BpjsDashboardController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\IncidentController;
use App\Http\Controllers\MaintenanceWindowController;
use App\Http\Controllers\MonitorController;
use App\Http\Controllers\NotificationChannelController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\SlaReportController;
use App\Http\Controllers\StatusPageController;
use Illuminate\Support\Facades\Route;

// Auth
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.attempt');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// Halaman publik — tidak butuh login
Route::get('/status/{slug}', [StatusPageController::class, 'show'])->name('status.public');
Route::get('/push/{token}', [MonitorController::class, 'receivePush'])->name('monitors.push');

Route::middleware('auth')->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
    Route::post('/check-all', [DashboardController::class, 'checkAll'])->name('dashboard.check-all');

    Route::resource('monitors', MonitorController::class);
    Route::post('monitors/{monitor}/check', [MonitorController::class, 'checkNow'])->name('monitors.check-now');
    Route::patch('monitors/{monitor}/toggle', [MonitorController::class, 'toggle'])->name('monitors.toggle');
    Route::post('monitors/{monitor}/silence', [MonitorController::class, 'silence'])->name('monitors.silence');

    Route::prefix('api-health')->name('api-health.')->group(function () {
        Route::get('/', [BpjsDashboardController::class, 'index'])->name('dashboard');
        Route::post('/check-all', [BpjsDashboardController::class, 'checkAll'])->name('check-all');
        Route::post('/check/{serviceKey}', [BpjsDashboardController::class, 'checkService'])->name('check-service');
        Route::post('/mode/{mode}', [BpjsDashboardController::class, 'switchMode'])->name('switch-mode');
    });

    Route::resource('channels', NotificationChannelController::class)->except(['show']);
    Route::post('channels/{channel}/test', [NotificationChannelController::class, 'test'])->name('channels.test');

    Route::resource('maintenance', MaintenanceWindowController::class)->except(['show']);
    Route::resource('status-pages', StatusPageController::class)->except(['show']);
    Route::resource('incidents', IncidentController::class)->except(['show']);
    Route::get('/sla-report', [SlaReportController::class, 'index'])->name('sla-report.index');

    Route::resource('tags', TagController::class)->except(['show', 'create', 'edit']);
    Route::get('/audit-logs', [AuditLogController::class, 'index'])->name('audit-logs.index');
    Route::resource('escalations', EscalationController::class)->except(['show']);

    Route::get('/settings', [SettingController::class, 'index'])->name('settings.index');
    Route::put('/settings', [SettingController::class, 'update'])->name('settings.update');
    Route::post('/settings/ip-info', [SettingController::class, 'ipInfo'])->name('settings.ip-info');
    Route::get('/settings/notifications', [SettingController::class, 'notifications'])->name('settings.notifications');
    Route::post('/settings/notifications', [SettingController::class, 'saveNotifications'])->name('settings.notifications.save');
    Route::post('/settings/report', [SettingController::class, 'saveReportSettings'])->name('settings.report.save');
    Route::post('/settings/report/test', [SettingController::class, 'sendTestReport'])->name('settings.report.test');
});
