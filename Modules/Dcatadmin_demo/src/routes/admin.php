<?php

use Illuminate\Support\Facades\Route;
use DcatAdminDemo\Http\Controllers\AdminDemoController;

Route::group([
    'prefix' => config('admin.route.prefix'),
    'middleware' => config('admin.route.middleware'),
], function () {
    // MAdminDemo 模块的后台路由
    Route::resource('madmindemo', AdminDemoController::class)->names('madmindemo');
});