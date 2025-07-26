<?php

use Illuminate\Support\Facades\Route;
use $MODULE_NAMESPACE$\Test\$CONTROLLER_NAMESPACE$\TestController;

Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    Route::apiResource('tests', TestController::class)->names('test');
});
