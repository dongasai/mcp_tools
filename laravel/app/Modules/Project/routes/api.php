<?php

use Illuminate\Support\Facades\Route;
use App\Modules\Project\Controllers\ProjectController;
use App\Modules\Project\Controllers\ProjectTestController;

/*
|--------------------------------------------------------------------------
| Project API Routes
|--------------------------------------------------------------------------
|
| Project模块的API路由定义
|
*/

Route::prefix('api/projects')->group(function () {
    // 测试路由（无需认证）
    Route::post('/test/create', [ProjectTestController::class, 'quickCreate']);
    Route::get('/test/list', [ProjectTestController::class, 'getProjects']);
    Route::get('/test/stats', [ProjectTestController::class, 'getStats']);
    Route::get('/test/user/{userId}', [ProjectTestController::class, 'getUserProjects']);
    Route::get('/test/agent/{agentId}', [ProjectTestController::class, 'getAgentProjects']);
    Route::post('/test/{id}/activate', [ProjectTestController::class, 'testActivate']);
    Route::post('/test/{id}/complete', [ProjectTestController::class, 'testComplete']);
    Route::get('/test/{id}/stats', [ProjectTestController::class, 'getProjectStats']);

    // 需要认证的路由
    Route::middleware('auth:sanctum')->group(function () {
        // Project CRUD操作
        Route::get('/', [ProjectController::class, 'index']);
        Route::post('/', [ProjectController::class, 'store']);
        Route::get('/{project}', [ProjectController::class, 'show']);
        Route::put('/{project}', [ProjectController::class, 'update']);
        Route::delete('/{project}', [ProjectController::class, 'destroy']);
        
        // Project状态管理
        Route::post('/{project}/activate', [ProjectController::class, 'activate']);
        Route::post('/{project}/complete', [ProjectController::class, 'complete']);
    });
});
