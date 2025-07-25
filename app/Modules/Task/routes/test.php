<?php

use Illuminate\Support\Facades\Route;
use App\Modules\Task\Controllers\TaskModelTestController;
use App\Modules\Task\Controllers\TaskWorkflowTestController;

/*
|--------------------------------------------------------------------------
| Task Model Test Routes
|--------------------------------------------------------------------------
|
| 用于测试Task模型修复后的功能
|
*/

Route::prefix('task-test')->group(function () {
    Route::get('/model', [TaskModelTestController::class, 'testTaskModel']);
    Route::get('/database', [TaskModelTestController::class, 'testDatabaseCompatibility']);
    Route::get('/events', [TaskModelTestController::class, 'testEventListeners']);
});

// Task工作流测试路由
Route::prefix('task-workflow-test')->group(function () {
    Route::get('/state-machine', [TaskWorkflowTestController::class, 'testStateMachine']);
    Route::post('/transition', [TaskWorkflowTestController::class, 'testTransition']);
    Route::get('/sub-task-completion', [TaskWorkflowTestController::class, 'testSubTaskCompletion']);
    Route::post('/auto-complete-parent', [TaskWorkflowTestController::class, 'testAutoCompleteParent']);
    Route::get('/status-options', [TaskWorkflowTestController::class, 'getStatusOptions']);
    Route::post('/batch-test', [TaskWorkflowTestController::class, 'batchTest']);
});
