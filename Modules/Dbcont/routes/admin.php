<?php

use Illuminate\Support\Facades\Route;
use App\Admin\Controllers\DatabaseConnectionController;

Route::group([
    'prefix' => config('admin.route.prefix'),
    'middleware' => config('admin.route.middleware'),
], function () {
    Route::resource('dbcont/database-connections', DatabaseConnectionController::class);
});