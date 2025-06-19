<?php

namespace App\Admin\Controllers;

use App\Modules\Project\Models\Project;
use App\Modules\User\Models\User;
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
        return Grid::make(Project::with(['user', 'agent']), function (Grid $grid) {
            $grid->column('id')->sortable();
            $grid->column('name', '项目名称');
            $grid->column('user.name', '用户');
            $grid->column('agent.name', 'Agent');
            $grid->column('status', '状态')->using([
                Project::STATUS_ACTIVE => '激活',
                Project::STATUS_INACTIVE => '未激活',
                Project::STATUS_COMPLETED => '已完成',
                Project::STATUS_ARCHIVED => '已归档',
                Project::STATUS_SUSPENDED => '已暂停',
            ])->label([
                Project::STATUS_ACTIVE => 'success',
                Project::STATUS_INACTIVE => 'default',
                Project::STATUS_COMPLETED => 'primary',
                Project::STATUS_ARCHIVED => 'info',
                Project::STATUS_SUSPENDED => 'warning',
            ]);
            $grid->column('priority', '优先级')->using([
                Project::PRIORITY_LOW => '低',
                Project::PRIORITY_MEDIUM => '中',
                Project::PRIORITY_HIGH => '高',
                Project::PRIORITY_URGENT => '紧急',
            ])->label([
                Project::PRIORITY_LOW => 'default',
                Project::PRIORITY_MEDIUM => 'info',
                Project::PRIORITY_HIGH => 'warning',
                Project::PRIORITY_URGENT => 'danger',
            ]);
            $grid->column('repository_url', '仓库地址')->limit(50);
            $grid->column('branch', '分支');
            $grid->column('created_at', '创建时间');
            $grid->column('updated_at', '更新时间')->sortable();

            $grid->filter(function (Grid\Filter $filter) {
                $filter->equal('id');
                $filter->like('name', '项目名称');
                $filter->equal('user_id', '用户')->select(User::pluck('name', 'id'));
                $filter->equal('agent_id', 'Agent')->select(Agent::pluck('name', 'id'));
                $filter->equal('status', '状态')->select([
                    Project::STATUS_ACTIVE => '激活',
                    Project::STATUS_INACTIVE => '未激活',
                    Project::STATUS_COMPLETED => '已完成',
                    Project::STATUS_ARCHIVED => '已归档',
                    Project::STATUS_SUSPENDED => '已暂停',
                ]);
                $filter->equal('priority', '优先级')->select([
                    Project::PRIORITY_LOW => '低',
                    Project::PRIORITY_MEDIUM => '中',
                    Project::PRIORITY_HIGH => '高',
                    Project::PRIORITY_URGENT => '紧急',
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
        return Show::make($id, Project::with(['user', 'agent']), function (Show $show) {
            $show->field('id');
            $show->field('name', '项目名称');
            $show->field('description', '项目描述');
            $show->field('user.name', '用户');
            $show->field('agent.name', 'Agent');
            $show->field('status', '状态')->using([
                Project::STATUS_ACTIVE => '激活',
                Project::STATUS_INACTIVE => '未激活',
                Project::STATUS_COMPLETED => '已完成',
                Project::STATUS_ARCHIVED => '已归档',
                Project::STATUS_SUSPENDED => '已暂停',
            ]);
            $show->field('priority', '优先级')->using([
                Project::PRIORITY_LOW => '低',
                Project::PRIORITY_MEDIUM => '中',
                Project::PRIORITY_HIGH => '高',
                Project::PRIORITY_URGENT => '紧急',
            ]);
            $show->field('repository_url', '仓库地址');
            $show->field('branch', '分支');
            $show->field('settings', '设置')->json();
            $show->field('metadata', '元数据')->json();
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
            $form->select('agent_id', 'Agent')->options(Agent::pluck('name', 'id'));
            $form->select('status', '状态')->options([
                Project::STATUS_ACTIVE => '激活',
                Project::STATUS_INACTIVE => '未激活',
                Project::STATUS_COMPLETED => '已完成',
                Project::STATUS_ARCHIVED => '已归档',
                Project::STATUS_SUSPENDED => '已暂停',
            ])->default(Project::STATUS_ACTIVE)->required();
            $form->select('priority', '优先级')->options([
                Project::PRIORITY_LOW => '低',
                Project::PRIORITY_MEDIUM => '中',
                Project::PRIORITY_HIGH => '高',
                Project::PRIORITY_URGENT => '紧急',
            ])->default(Project::PRIORITY_MEDIUM)->required();
            $form->url('repository_url', '仓库地址');
            $form->text('branch', '分支')->default('main');
            $form->json('settings', '设置');
            $form->json('metadata', '元数据');

            $form->display('created_at', '创建时间');
            $form->display('updated_at', '更新时间');
        });
    }
}
