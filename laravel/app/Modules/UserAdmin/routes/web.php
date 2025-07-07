<?php

use Illuminate\Support\Facades\Route;
use App\Modules\UserAdmin\Controllers\DashboardController;
use App\Modules\UserAdmin\Controllers\ProjectController;
use App\Modules\UserAdmin\Controllers\TaskController;
use App\Modules\UserAdmin\Controllers\AgentController;
use App\Modules\UserAdmin\Controllers\ProfileController;

/*
|--------------------------------------------------------------------------
| User Admin Routes
|--------------------------------------------------------------------------
|
| 用户后台路由定义
|
*/

Route::group([
    'middleware' => ['auth'],
], function () {
    
    // 仪表板
    Route::get('/', [DashboardController::class, 'index'])->name('user-admin.dashboard');
    
    // 我的项目
    Route::prefix('projects')->name('user-admin.projects.')->group(function () {
        Route::get('/', [ProjectController::class, 'index'])->name('index');
        Route::get('/create', [ProjectController::class, 'create'])->name('create');
        Route::post('/', [ProjectController::class, 'store'])->name('store');
        Route::get('/{project}', [ProjectController::class, 'show'])->name('show');
        Route::get('/{project}/edit', [ProjectController::class, 'edit'])->name('edit');
        Route::put('/{project}', [ProjectController::class, 'update'])->name('update');
        Route::delete('/{project}', [ProjectController::class, 'destroy'])->name('destroy');
        
        // 项目统计
        Route::get('/{project}/stats', [ProjectController::class, 'stats'])->name('stats');
    });
    
    // 我的任务
    Route::prefix('tasks')->name('user-admin.tasks.')->group(function () {
        Route::get('/', [TaskController::class, 'index'])->name('index');
        Route::get('/create', [TaskController::class, 'create'])->name('create');
        Route::post('/', [TaskController::class, 'store'])->name('store');
        Route::get('/{task}', [TaskController::class, 'show'])->name('show');
        Route::get('/{task}/edit', [TaskController::class, 'edit'])->name('edit');
        Route::put('/{task}', [TaskController::class, 'update'])->name('update');
        Route::delete('/{task}', [TaskController::class, 'destroy'])->name('destroy');
        
        // 任务操作
        Route::post('/{task}/start', [TaskController::class, 'start'])->name('start');
        Route::post('/{task}/complete', [TaskController::class, 'complete'])->name('complete');
        Route::post('/{task}/pause', [TaskController::class, 'pause'])->name('pause');
    });
    
    // 我的Agent
    Route::prefix('agents')->name('user-admin.agents.')->group(function () {
        Route::get('/', [AgentController::class, 'index'])->name('index');
        Route::get('/create', [AgentController::class, 'create'])->name('create');
        Route::post('/', [AgentController::class, 'store'])->name('store');
        Route::get('/{agent}', [AgentController::class, 'show'])->name('show');
        Route::get('/{agent}/edit', [AgentController::class, 'edit'])->name('edit');
        Route::put('/{agent}', [AgentController::class, 'update'])->name('update');
        Route::delete('/{agent}', [AgentController::class, 'destroy'])->name('destroy');
        
        // Agent操作
        Route::post('/{agent}/activate', [AgentController::class, 'activate'])->name('activate');
        Route::post('/{agent}/deactivate', [AgentController::class, 'deactivate'])->name('deactivate');
        Route::get('/{agent}/logs', [AgentController::class, 'logs'])->name('logs');
    });
    
    // 个人设置
    Route::prefix('profile')->name('user-admin.profile.')->group(function () {
        Route::get('/', [ProfileController::class, 'index'])->name('index');
        Route::put('/', [ProfileController::class, 'update'])->name('update');
        Route::get('/password', [ProfileController::class, 'password'])->name('password');
        Route::put('/password', [ProfileController::class, 'updatePassword'])->name('update-password');
        Route::get('/settings', [ProfileController::class, 'settings'])->name('settings');
        Route::put('/settings', [ProfileController::class, 'updateSettings'])->name('update-settings');
    });
    
});
