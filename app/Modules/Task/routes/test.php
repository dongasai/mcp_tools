<?php

use Illuminate\Support\Facades\Route;
use App\Modules\Task\Controllers\TaskModelTestController;

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
});
