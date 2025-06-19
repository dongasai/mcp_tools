<?php

use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Route;
use Dcat\Admin\Admin;

Admin::routes();

Route::group([
    'prefix'     => config('admin.route.prefix'),
    'namespace'  => config('admin.route.namespace'),
    'middleware' => config('admin.route.middleware'),
], function (Router $router) {

    $router->get('/', 'HomeController@index');

    // 业务模块路由
    $router->resource('users', 'UserController');
    $router->resource('projects', 'ProjectController');
    $router->resource('agents', 'AgentController');
    $router->resource('tasks', 'TaskController');

});
