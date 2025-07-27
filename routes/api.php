<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// 包含Task模块测试路由
require_once __DIR__ . '/../Modules/Task/routes/test.php';

// 包含MCP模块测试路由
require_once __DIR__ . '/../Modules/MCP/routes/test.php';

/*
|--------------------------------------------------------------------------
| API Routes - 各模块API路由
|--------------------------------------------------------------------------
|
| 各模块的API路由在此定义
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});