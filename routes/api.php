<?php

use App\Http\Controllers\Api\MonitorApiController;
use Illuminate\Support\Facades\Route;

Route::middleware(\App\Http\Middleware\ApiTokenMiddleware::class)->group(function () {
    Route::get('/monitors', [MonitorApiController::class, 'index']);
    Route::get('/monitors/summary', [MonitorApiController::class, 'statusSummary']);
    Route::get('/monitors/{id}', [MonitorApiController::class, 'show']);
    Route::get('/monitors/{id}/incidents', [MonitorApiController::class, 'incidents']);
    Route::post('/monitors/{id}/heartbeat', [MonitorApiController::class, 'trigger']);
});
