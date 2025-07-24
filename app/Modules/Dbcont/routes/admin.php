<?php

use Illuminate\Support\Facades\Route;
use App\Admin\Controllers\DatabaseConnectionController;

Route::resource('dbcont/database-connections', DatabaseConnectionController::class);