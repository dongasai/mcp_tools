<?php

namespace App\Admin\Controllers;

use App\Modules\Project\Models\Project;
use Modules\User\Models\User;
use App\Modules\Agent\Models\Agent;
use Dcat\Admin\Form;
use Dcat\Admin\Grid;
use Dcat\Admin\Show;
use Dcat\Admin\Http\Controllers\AdminController;

class ProjectController extends AdminController
{
    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        return Grid::make(Project::with(['user']), function (Grid $grid) {
            $grid->column('id')->sortable();
            $grid->column('name', '项目名称');
            $grid->column('description', '项目描述')->limit(50);
            $grid->column('user.name', '用户');
            $grid->column('status', '状态')->using([
                'active' => '激活',
                'inactive' => '未激活',
                'archived' => '已归档',
            ])->label([
                'active' => 'success',
                'inactive' => 'default',
                'archived' => 'info',
            ]);
            $grid->column('timezone', '时区');
            $grid->column('created_at', '创建时间');
            $grid->column('updated_at', '更新时间')->sortable();

            $grid->filter(function (Grid\Filter $filter) {
                $filter->equal('id');
                $filter->like('name', '项目名称');
                $filter->equal('user_id', '用户')->select(User::pluck('name', 'id'));
                $filter->equal('status', '状态')->select([
                    'active' => '激活',
                    'inactive' => '未激活',
                    'archived' => '已归档',
                ]);
            });
        });
    }

    /**
     * Make a show builder.
     *
     * @param mixed $id
     *
     * @return Show
     */
    protected function detail($id)
    {
        return Show::make($id, Project::with(['user']), function (Show $show) {
            $show->field('id');
            $show->field('name', '项目名称');
            $show->field('description', '项目描述');
            $show->field('user.name', '用户');
            $show->field('status', '状态')->using([
                'active' => '激活',
                'inactive' => '未激活',
                'archived' => '已归档',
            ]);
            $show->field('timezone', '时区');
            $show->field('repositories', '仓库')->json();
            $show->field('settings', '设置')->json();
            $show->field('created_at', '创建时间');
            $show->field('updated_at', '更新时间');
        });
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        return Form::make(Project::query(), function (Form $form) {
            $form->display('id');
            $form->text('name', '项目名称')->required();
            $form->textarea('description', '项目描述');
            $form->select('user_id', '用户')->options(User::pluck('name', 'id'))->required();
            $form->select('status', '状态')->options([
                'active' => '激活',
                'inactive' => '未激活',
                'archived' => '已归档',
            ])->default('active')->required();
            $form->text('timezone', '时区')->default('UTC');
            $form->textarea('repositories', '仓库')->placeholder('JSON格式的仓库信息');
            $form->textarea('settings', '设置')->placeholder('JSON格式的设置信息');

            $form->display('created_at', '创建时间');
            $form->display('updated_at', '更新时间');
        });
    }
}
