<?php

use Illuminate\Support\Facades\Route;
use App\Modules\Task\Controllers\SimpleTaskController;

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
});
