<?php

use Illuminate\Support\Facades\Route;
use $MODULE_NAMESPACE$\AdminDemo\$CONTROLLER_NAMESPACE$\AdminDemoController;

Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    Route::apiResource('admindemos', AdminDemoController::class)->names('admindemo');
});
