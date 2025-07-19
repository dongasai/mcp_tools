<?php

use Illuminate\Support\Facades\Route;
use App\Modules\Agent\Controllers\AgentTestController;

/*
|--------------------------------------------------------------------------
| Agent API Routes
|--------------------------------------------------------------------------
|
| Agent模块的测试路由定义（仅保留测试功能）
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
});
