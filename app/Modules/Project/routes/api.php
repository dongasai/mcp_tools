<?php

use Illuminate\Support\Facades\Route;
use App\Modules\Project\Controllers\ProjectTestController;

/*
|--------------------------------------------------------------------------
| Project API Routes
|--------------------------------------------------------------------------
|
| Project模块的测试路由定义（仅保留测试功能）
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
});
