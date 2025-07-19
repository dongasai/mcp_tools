<?php

use Illuminate\Support\Facades\Route;
use App\Modules\Task\Controllers\SimpleTaskController;
use App\Modules\Task\Controllers\TaskTestController;
use App\Modules\Task\Controllers\TaskCommentController;

/*
|--------------------------------------------------------------------------
| Task API Routes
|--------------------------------------------------------------------------
|
| Task模块的API路由定义
|
*/

Route::prefix('api/tasks')->group(function () {
    // 简化测试路由（无需认证）
    Route::get('/test/stats', [SimpleTaskController::class, 'getStats']);
    Route::get('/test/list', [SimpleTaskController::class, 'getTasks']);

    // 完整测试路由
    Route::post('/test/create', [TaskTestController::class, 'quickCreate']);
    Route::get('/test/user/{userId}', [TaskTestController::class, 'getUserTasks']);
    Route::get('/test/agent/{agentId}', [TaskTestController::class, 'getAgentTasks']);
    Route::post('/test/{id}/start', [TaskTestController::class, 'testStart']);
    Route::post('/test/{id}/complete', [TaskTestController::class, 'testComplete']);
    Route::get('/test/{id}/progress', [TaskTestController::class, 'getProgress']);
});

// 评论相关路由
Route::prefix('api/tasks/{task}')->group(function () {
    // 评论CRUD操作
    Route::get('/comments', [TaskCommentController::class, 'index']);
    Route::post('/comments', [TaskCommentController::class, 'store']);
    Route::get('/comments/{comment}', [TaskCommentController::class, 'show']);
    Route::put('/comments/{comment}', [TaskCommentController::class, 'update']);
    Route::delete('/comments/{comment}', [TaskCommentController::class, 'destroy']);

    // 评论回复操作
    Route::post('/comments/{comment}/reply', [TaskCommentController::class, 'reply']);
    Route::get('/comments/{comment}/replies', [TaskCommentController::class, 'replies']);
});
