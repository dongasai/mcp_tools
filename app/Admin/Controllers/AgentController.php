<?php

namespace App\Admin\Controllers;

use App\Modules\Agent\Models\Agent;
use App\Modules\User\Models\User;
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
        return Grid::make(Agent::with(['user']), function (Grid $grid) {
            $grid->column('id')->sortable();
            $grid->column('name', 'Agent名称');
            $grid->column('agent_id', 'Agent ID');
            $grid->column('user.name', '用户');
            $grid->column('type', '类型');
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
                $filter->like('agent_id', 'Agent ID');
                $filter->equal('user_id', '用户')->select(User::pluck('name', 'id'));
                $filter->like('type', '类型');
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
        return Show::make($id, Agent::with(['user']), function (Show $show) {
            $show->field('id');
            $show->field('name', 'Agent名称');
            $show->field('agent_id', 'Agent ID');
            $show->field('user.name', '用户');
            $show->field('type', '类型');
            $show->field('status', '状态')->using([
                Agent::STATUS_ACTIVE => '激活',
                Agent::STATUS_INACTIVE => '未激活',
                Agent::STATUS_SUSPENDED => '已暂停',
                Agent::STATUS_PENDING => '待审核',
            ]);
            $show->field('access_token', '访问令牌')->mask('*');
            $show->field('permissions', '权限')->json();
            $show->field('allowed_projects', '允许的项目')->json();
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
            $form->text('agent_id', 'Agent ID')->required();
            $form->select('user_id', '用户')->options(User::pluck('name', 'id'))->required();
            $form->text('type', '类型')->default('mcp_agent');
            $form->select('status', '状态')->options([
                Agent::STATUS_ACTIVE => '激活',
                Agent::STATUS_INACTIVE => '未激活',
                Agent::STATUS_SUSPENDED => '已暂停',
                Agent::STATUS_PENDING => '待审核',
            ])->default(Agent::STATUS_PENDING)->required();
            $form->password('access_token', '访问令牌')->required(function ($form) {
                return !$form->model()->id;
            });
            $form->textarea('permissions', '权限')->placeholder('JSON格式的权限配置');
            $form->textarea('allowed_projects', '允许的项目')->placeholder('JSON格式的项目列表');
            $form->textarea('allowed_actions', '允许的动作')->placeholder('JSON格式的动作列表');
            $form->datetime('last_active_at', '最后活跃时间');
            $form->datetime('token_expires_at', 'Token过期时间');

            $form->display('created_at', '创建时间');
            $form->display('updated_at', '更新时间');
        });
    }
}
