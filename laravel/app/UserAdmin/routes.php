<?php

use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Route;
use Dcat\Admin\Admin;

Admin::routes();

Route::group([
    'prefix'     => config('admin.route.prefix'),
    'namespace'  => config('admin.route.namespace'),
    'middleware' => array_merge(config('admin.route.middleware'), ['user-admin.resource-ownership']),
], function (Router $router) {

    // 仪表板
    $router->get('/', 'DashboardController@index');

    // 用户管理
    $router->resource('users', 'UserController');

    // 项目管理
    $router->resource('projects', 'ProjectController');

    // 任务管理
    $router->resource('tasks', 'TaskController');

    // Agent管理
    $router->resource('agents', 'AgentController');

    // 个人设置
    $router->get('profile', 'ProfileController@index');
    $router->post('profile/update', 'ProfileController@updateProfile');

    // GitHub集成
    $router->get('github', 'GitHubController@index');
    $router->post('github/connect', 'GitHubController@connect');
    $router->delete('github/disconnect', 'GitHubController@disconnect');

});
