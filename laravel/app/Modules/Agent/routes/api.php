<?php

use Illuminate\Support\Facades\Route;
use App\Modules\Agent\Controllers\AgentController;
use App\Modules\Agent\Controllers\AgentTestController;

/*
|--------------------------------------------------------------------------
| Agent API Routes
|--------------------------------------------------------------------------
|
| Agent模块的API路由定义
|
*/

Route::prefix('api/agents')->group(function () {
    // 测试路由（无需认证）
    Route::post('/test/create', [AgentTestController::class, 'quickCreate']);
    Route::get('/test/list', [AgentTestController::class, 'getAgents']);
    Route::get('/test/stats', [AgentTestController::class, 'getStats']);
    Route::get('/test/find/{agentId}', [AgentTestController::class, 'findByAgentId']);
    Route::post('/test/{id}/activate', [AgentTestController::class, 'testActivate']);
    Route::post('/test/{id}/deactivate', [AgentTestController::class, 'testDeactivate']);

    // 需要认证的路由
    Route::middleware('auth:sanctum')->group(function () {
        // Agent CRUD操作
        Route::get('/', [AgentController::class, 'index']);
        Route::post('/', [AgentController::class, 'store']);
        Route::get('/{agent}', [AgentController::class, 'show']);
        Route::put('/{agent}', [AgentController::class, 'update']);
        Route::delete('/{agent}', [AgentController::class, 'destroy']);

        // Agent状态管理
        Route::post('/{agent}/activate', [AgentController::class, 'activate']);
        Route::post('/{agent}/deactivate', [AgentController::class, 'deactivate']);
    });
});
