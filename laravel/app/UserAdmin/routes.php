<?php

use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Route;
use Dcat\Admin\Admin;

// 注册用户后台的 dcat-admin 路由
Admin::routes();

Route::group([
    'prefix'     => config('user-admin.route.prefix'),
    'namespace'  => config('user-admin.route.namespace'),
    'middleware' => config('user-admin.route.middleware'),
], function (Router $router) {

    // 仪表板
    $router->get('/', 'DashboardController@index');

    // 用户管理
    $router->resource('users', 'UserController');

    // 项目管理
    $router->resource('projects', 'ProjectController');

    // 项目成员管理
    $router->get('projects/{project}/members', 'MemberController@index')->name('projects.members.index');
    $router->get('projects/{project}/members/create', 'MemberController@create')->name('projects.members.create');
    $router->post('projects/{project}/members', 'MemberController@store')->name('projects.members.store');
    $router->get('projects/{project}/members/{member}', 'MemberController@show')->name('projects.members.show');
    $router->get('projects/{project}/members/{member}/edit', 'MemberController@edit')->name('projects.members.edit');
    $router->put('projects/{project}/members/{member}', 'MemberController@update')->name('projects.members.update');
    $router->delete('projects/{project}/members/{member}', 'MemberController@destroy')->name('projects.members.destroy');
    $router->post('projects/{project}/members/batch-add', 'MemberController@batchAdd')->name('projects.members.batch-add');
    $router->post('projects/{project}/transfer-ownership', 'MemberController@transferOwnership')->name('projects.transfer-ownership');

    // 任务管理
    $router->resource('tasks', 'TaskController');

    // 任务评论管理
    $router->post('tasks/{task}/comments', 'TaskController@addComment')->name('tasks.comments.store');
    $router->put('tasks/{task}/comments/{comment}', 'TaskController@editComment')->name('tasks.comments.update');
    $router->delete('tasks/{task}/comments/{comment}', 'TaskController@deleteComment')->name('tasks.comments.destroy');

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
