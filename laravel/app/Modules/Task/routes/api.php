<?php

use Illuminate\Support\Facades\Route;
use App\Modules\Task\Controllers\TaskController;
use App\Modules\Task\Controllers\TaskTestController;

/*
|--------------------------------------------------------------------------
| Task API Routes
|--------------------------------------------------------------------------
|
| Task模块的API路由定义
|
*/

Route::prefix('api/tasks')->group(function () {
    // 测试路由（无需认证）
    Route::post('/test/create', [TaskTestController::class, 'quickCreate']);
    Route::get('/test/list', [TaskTestController::class, 'getTasks']);
    Route::get('/test/stats', [TaskTestController::class, 'getStats']);
    Route::get('/test/user/{userId}', [TaskTestController::class, 'getUserTasks']);
    Route::get('/test/project/{projectId}', [TaskTestController::class, 'getProjectTasks']);
    Route::get('/test/agent/{agentId}', [TaskTestController::class, 'getAgentTasks']);
    Route::post('/test/{id}/start', [TaskTestController::class, 'testStart']);
    Route::post('/test/{id}/complete', [TaskTestController::class, 'testComplete']);
    Route::get('/test/{id}/details', [TaskTestController::class, 'getTaskDetails']);
    Route::post('/test/{parentId}/sub-task', [TaskTestController::class, 'createSubTask']);

    // 需要认证的路由
    Route::middleware('auth:sanctum')->group(function () {
        // Task CRUD操作
        Route::get('/', [TaskController::class, 'index']);
        Route::post('/', [TaskController::class, 'store']);
        Route::get('/{task}', [TaskController::class, 'show']);
        Route::put('/{task}', [TaskController::class, 'update']);
        Route::delete('/{task}', [TaskController::class, 'destroy']);
        
        // Task状态管理
        Route::post('/{task}/start', [TaskController::class, 'start']);
        Route::post('/{task}/complete', [TaskController::class, 'complete']);
        
        // Task子任务
        Route::get('/{task}/sub-tasks', [TaskController::class, 'subTasks']);
    });
});
