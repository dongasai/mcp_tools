<?php

use Illuminate\Support\Facades\Route;
use $MODULE_NAMESPACE$\MAdminDemo\$CONTROLLER_NAMESPACE$\MAdminDemoController;

Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    Route::apiResource('madmindemos', MAdminDemoController::class)->names('madmindemo');
});
