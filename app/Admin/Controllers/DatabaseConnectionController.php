<?php

namespace App\Admin\Controllers;

use App\Modules\Dbcont\Models\DatabaseConnection;
use Dcat\Admin\Controllers\AdminController;
use Dcat\Admin\Form;
use Dcat\Admin\Grid;

class DatabaseConnectionController extends AdminController
{
    protected $title = '数据库连接管理';

    protected function grid()
    {
        return Grid::make(new DatabaseConnection(), function (Grid $grid) {
            $grid->column('id', 'ID')->sortable();
            $grid->column('name', '名称');
            $grid->column('driver', '驱动');
            $grid->column('host', '主机');
            $grid->column('port', '端口');
            $grid->column('database', '数据库名');
            $grid->column('username', '用户名');
            $grid->column('created_at', '创建时间');
            $grid->column('updated_at', '更新时间');
            
            $grid->actions(function (Grid\Displayers\Actions $actions) {
                $actions->disableView();
            });
        });
    }

    protected function form()
    {
        return Form::make(new DatabaseConnection(), function (Form $form) {
            $form->display('id', 'ID');
            $form->text('name', '名称')->required();
            $form->select('driver', '驱动')->options([
                'mysql' => 'MySQL',
                'pgsql' => 'PostgreSQL',
                'sqlsrv' => 'SQL Server',
                'sqlite' => 'SQLite',
            ])->required();
            $form->text('host', '主机')->required();
            $form->number('port', '端口')->required();
            $form->text('database', '数据库名')->required();
            $form->text('username', '用户名')->required();
            $form->password('password', '密码')->required();
            $form->display('created_at', '创建时间');
            $form->display('updated_at', '更新时间');
        });
    }
}