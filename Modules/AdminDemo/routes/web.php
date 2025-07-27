<?php

use Illuminate\Support\Facades\Route;
use $MODULE_NAMESPACE$\AdminDemo\$CONTROLLER_NAMESPACE$\AdminDemoController;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::resource('admindemos', AdminDemoController::class)->names('admindemo');
});
