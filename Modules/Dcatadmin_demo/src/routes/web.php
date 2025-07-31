<?php

use Illuminate\Support\Facades\Route;
use $MODULE_NAMESPACE$\MAdminDemo\$CONTROLLER_NAMESPACE$\MAdminDemoController;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::resource('madmindemos', MAdminDemoController::class)->names('madmindemo');
});
