<?php

namespace App\Admin\Controllers;

use App\Modules\Mcp\Models\Agent;
use App\Modules\User\Models\User;
use App\Modules\Project\Models\Project;
use Dcat\Admin\Form;
use Dcat\Admin\Grid;
use Dcat\Admin\Show;
use Dcat\Admin\Http\Controllers\AdminController;

class AgentController extends AdminController
{
    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        return Grid::make(Agent::with(['user', 'project']), function (Grid $grid) {
            $grid->column('id')->sortable();
            $grid->column('name', 'Agent名称');
            $grid->column('identifier', 'Agent ID');
            $grid->column('user.name', '用户');
            $grid->column('project.name', '所属项目');
            $grid->column('status', '状态')->using([
                Agent::STATUS_ACTIVE => '激活',
                Agent::STATUS_INACTIVE => '未激活',
                Agent::STATUS_SUSPENDED => '已暂停',
                Agent::STATUS_PENDING => '待审核',
            ])->label([
                Agent::STATUS_ACTIVE => 'success',
                Agent::STATUS_INACTIVE => 'default',
                Agent::STATUS_SUSPENDED => 'danger',
                Agent::STATUS_PENDING => 'warning',
            ]);
            $grid->column('last_active_at', '最后活跃时间');
            $grid->column('token_expires_at', 'Token过期时间');
            $grid->column('created_at', '创建时间');
            $grid->column('updated_at', '更新时间')->sortable();

            $grid->filter(function (Grid\Filter $filter) {
                $filter->equal('id');
                $filter->like('name', 'Agent名称');
                $filter->like('identifier', 'Agent ID');
                $filter->equal('user_id', '用户')->select(User::pluck('name', 'id'));
                $filter->equal('project_id', '项目')->select(Project::pluck('name', 'id'));
                $filter->equal('status', '状态')->select([
                    Agent::STATUS_ACTIVE => '激活',
                    Agent::STATUS_INACTIVE => '未激活',
                    Agent::STATUS_SUSPENDED => '已暂停',
                    Agent::STATUS_PENDING => '待审核',
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
        return Show::make($id, Agent::with(['user', 'project']), function (Show $show) {
            $show->field('id');
            $show->field('name', 'Agent名称');
            $show->field('identifier', 'Agent ID');
            $show->field('user.name', '用户');
            $show->field('project.name', '所属项目');
            $show->field('status', '状态')->using([
                Agent::STATUS_ACTIVE => '激活',
                Agent::STATUS_INACTIVE => '未激活',
                Agent::STATUS_SUSPENDED => '已暂停',
                Agent::STATUS_PENDING => '待审核',
            ]);
            $show->field('access_token', '访问令牌')->mask('*');
            $show->field('capabilities', '能力')->json();
            $show->field('configuration', '配置')->json();
            $show->field('allowed_actions', '允许的动作')->json();
            $show->field('last_active_at', '最后活跃时间');
            $show->field('token_expires_at', 'Token过期时间');
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
        return Form::make(Agent::query(), function (Form $form) {
            $form->display('id');
            $form->text('name', 'Agent名称')->required();
            $form->text('identifier', 'Agent ID')->required();
            $form->select('user_id', '用户')->options(User::pluck('name', 'id'))->required();
            $form->select('project_id', '所属项目')->options(Project::pluck('name', 'id'))->required();
            $form->select('status', '状态')->options([
                Agent::STATUS_ACTIVE => '激活',
                Agent::STATUS_INACTIVE => '未激活',
                Agent::STATUS_SUSPENDED => '已暂停',
                Agent::STATUS_PENDING => '待审核',
            ])->default(Agent::STATUS_PENDING)->required();
            $form->password('access_token', '访问令牌')->required(function ($form) {
                return !$form->model()->id;
            });
            $form->textarea('description', '描述');
            $form->textarea('capabilities', '能力')->placeholder('JSON格式的能力配置');
            $form->textarea('configuration', '配置')->placeholder('JSON格式的配置信息');
            $form->textarea('allowed_actions', '允许的动作')->placeholder('JSON格式的动作列表');
            $form->datetime('last_active_at', '最后活跃时间');
            $form->datetime('token_expires_at', 'Token过期时间');

            $form->display('created_at', '创建时间');
            $form->display('updated_at', '更新时间');
        });
    }
}
