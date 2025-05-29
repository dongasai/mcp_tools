<?php

use Illuminate\Support\Facades\Route;
use App\Modules\Core\Controllers\HealthController;
use App\Modules\Core\Controllers\ConfigController;
use App\Modules\Core\Controllers\LogController;

/*
|--------------------------------------------------------------------------
| Core API Routes
|--------------------------------------------------------------------------
|
| 核心模块的API路由定义
|
*/

Route::prefix('api/core')->group(function () {
    // 健康检查
    Route::get('/health', [HealthController::class, 'check']);
    Route::get('/health/detailed', [HealthController::class, 'detailed']);
    
    // 系统信息
    Route::get('/info', [HealthController::class, 'info']);
    
    // 配置管理（需要认证）
    Route::middleware(['auth:api'])->group(function () {
        Route::get('/config', [ConfigController::class, 'index']);
        Route::get('/config/{key}', [ConfigController::class, 'show']);
        Route::post('/config', [ConfigController::class, 'store']);
        Route::put('/config/{key}', [ConfigController::class, 'update']);
        Route::delete('/config/{key}', [ConfigController::class, 'destroy']);
        Route::post('/config/refresh', [ConfigController::class, 'refresh']);
    });
    
    // 日志管理（需要管理员权限）
    Route::middleware(['auth:api', 'admin'])->group(function () {
        Route::get('/logs', [LogController::class, 'index']);
        Route::get('/logs/stats', [LogController::class, 'stats']);
        Route::post('/logs/cleanup', [LogController::class, 'cleanup']);
        Route::get('/logs/audit', [LogController::class, 'audit']);
    });
});
