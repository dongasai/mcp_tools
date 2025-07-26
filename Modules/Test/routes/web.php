<?php

use Illuminate\Support\Facades\Route;
use $MODULE_NAMESPACE$\Test\$CONTROLLER_NAMESPACE$\TestController;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::resource('tests', TestController::class)->names('test');
});
