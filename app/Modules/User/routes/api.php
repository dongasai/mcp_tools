<?php

use Illuminate\Support\Facades\Route;

use App\Modules\User\Controllers\AuthController;
use App\Modules\User\Controllers\ProfileController;
use App\Modules\User\Controllers\TestController;
use App\Modules\User\Controllers\SimpleAuthController;
use App\Modules\User\Controllers\QuickTestController;

/*
|--------------------------------------------------------------------------
| User API Routes
|--------------------------------------------------------------------------
|
| 用户模块的API路由定义
|
*/

Route::prefix('api/users')->group(function () {
    // 测试路由
    Route::get('/test/simple', [TestController::class, 'simple']);
    Route::post('/test/simple-post', [TestController::class, 'simplePost']);
    Route::post('/test/validator', [TestController::class, 'testValidator']);
    Route::get('/test/database', [TestController::class, 'testDatabase']);

    // 简化认证路由
    Route::post('/simple/register', [SimpleAuthController::class, 'register']);
    Route::post('/simple/login', [SimpleAuthController::class, 'login']);
    Route::post('/simple/logout', [SimpleAuthController::class, 'logout']);
    Route::get('/simple/me', [SimpleAuthController::class, 'me']);

    // 快速测试路由
    Route::post('/quick/register', [QuickTestController::class, 'quickRegister']);
    Route::post('/quick/login', [QuickTestController::class, 'quickLogin']);
    Route::get('/quick/users', [QuickTestController::class, 'getUsers']);
    // 公开路由
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
    Route::post('/reset-password', [AuthController::class, 'resetPassword']);
    Route::get('/verify-email/{token}', [AuthController::class, 'verifyEmail']);

    // 需要认证的路由
    Route::middleware(['auth:api'])->group(function () {
        // 个人资料管理
        Route::get('/profile', [ProfileController::class, 'show']);
        Route::put('/profile', [ProfileController::class, 'update']);
        Route::post('/profile/avatar', [ProfileController::class, 'uploadAvatar']);
        Route::put('/profile/password', [ProfileController::class, 'changePassword']);
        Route::put('/profile/settings', [ProfileController::class, 'updateSettings']);

        // 登出
        Route::post('/logout', [AuthController::class, 'logout']);

        // 用户管理功能已移除，改为使用dcat-admin管理后台
    });
});
