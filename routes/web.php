<?php

use App\Http\Controllers\AuditLogController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BusinessHourController;
use App\Http\Controllers\EscalationController;
use App\Http\Controllers\ImportExportController;
use App\Http\Controllers\MonitorTemplateController;
use App\Http\Controllers\OnCallScheduleController;
use App\Http\Controllers\SlaContractController;
use App\Http\Controllers\TagController;
use App\Http\Controllers\TelegramChatbotController;
use App\Http\Controllers\WebhookInboundController;
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
Route::get('/status/{slug}/widget', [StatusPageController::class, 'widget'])->name('status.widget');
Route::get('/status/{slug}/badge.svg', [StatusPageController::class, 'badge'])->name('status.badge');
Route::get('/push/{token}', [MonitorController::class, 'receivePush'])->name('monitors.push');
Route::get('/heartbeat/{token}', [MonitorController::class, 'receiveCronHeartbeat'])->name('monitors.heartbeat');
Route::get('/events/monitors', [StatusPageController::class, 'stream'])->name('monitors.sse');

// Webhook inbound receiver (public endpoint per token)
Route::post('/webhook-in/{token}', [WebhookInboundController::class, 'receive'])->name('webhook-inbound.receive');

// Telegram chatbot webhook
Route::post('/telegram/webhook', [TelegramChatbotController::class, 'webhook'])->name('telegram.webhook');

// Custom domain fallback — cocokkan host ke status_pages.custom_domain
Route::fallback([StatusPageController::class, 'showByDomain']);

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

    // Import/Export
    Route::get('/import', [ImportExportController::class, 'page'])->name('monitors.import-page');
    Route::get('/export', [ImportExportController::class, 'export'])->name('monitors.export');
    Route::post('/import', [ImportExportController::class, 'import'])->name('monitors.import');
    Route::post('/import-csv', [ImportExportController::class, 'importCsv'])->name('monitors.import-csv');
    Route::get('/smoke-test', [ImportExportController::class, 'smokeTest'])->name('monitors.smoke-test');

    // Monitor clone
    Route::post('/monitors/{monitor}/clone', [MonitorController::class, 'clone'])->name('monitors.clone');

    // On-Call
    Route::resource('on-call', OnCallScheduleController::class)->except(['show']);

    // SLA Contracts
    Route::resource('sla', SlaContractController::class)->except(['show']);

    // Webhook inbound management
    Route::resource('webhook-inbound', WebhookInboundController::class)->except(['show', 'edit', 'update', 'create']);

    // Monitor templates
    Route::resource('templates', MonitorTemplateController::class)->except(['show', 'edit', 'update']);
    Route::post('/templates/{template}/apply', [MonitorTemplateController::class, 'apply'])->name('templates.apply');

    // Business hours
    Route::get('/business-hours', [BusinessHourController::class, 'index'])->name('business-hours.index');
    Route::post('/business-hours', [BusinessHourController::class, 'save'])->name('business-hours.save');

    // Incident post-mortem
    Route::get('/incidents/{incident}/post-mortem', [IncidentController::class, 'postMortemForm'])->name('incidents.post-mortem');
    Route::post('/incidents/{incident}/post-mortem', [IncidentController::class, 'savePostMortem'])->name('incidents.post-mortem.save');

    // Alert simulator
    Route::post('/monitors/{monitor}/simulate', [MonitorController::class, 'simulate'])->name('monitors.simulate');

    Route::get('/settings', [SettingController::class, 'index'])->name('settings.index');
    Route::put('/settings', [SettingController::class, 'update'])->name('settings.update');
    Route::post('/settings/ip-info', [SettingController::class, 'ipInfo'])->name('settings.ip-info');
    Route::get('/settings/notifications', [SettingController::class, 'notifications'])->name('settings.notifications');
    Route::post('/settings/notifications', [SettingController::class, 'saveNotifications'])->name('settings.notifications.save');
    Route::post('/settings/report', [SettingController::class, 'saveReportSettings'])->name('settings.report.save');
    Route::post('/settings/report/test', [SettingController::class, 'sendTestReport'])->name('settings.report.test');

    // API Token management (web)
    Route::get('/api-tokens', [\App\Http\Controllers\ApiTokenController::class, 'index'])->name('api-tokens.index');
    Route::post('/api-tokens', [\App\Http\Controllers\ApiTokenController::class, 'store'])->name('api-tokens.store');
    Route::delete('/api-tokens/{token}', [\App\Http\Controllers\ApiTokenController::class, 'destroy'])->name('api-tokens.destroy');
});
