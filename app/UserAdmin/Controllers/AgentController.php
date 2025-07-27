<?php

namespace App\UserAdmin\Controllers;

use Dcat\Admin\Http\Controllers\AdminController;
use Dcat\Admin\Grid;
use Dcat\Admin\Form;
use Dcat\Admin\Show;
use Dcat\Admin\Layout\Content;
use Modules\MCP\Models\Agent;
use Modules\Project\Models\Project;
use Modules\User\Models\User;

class AgentController extends AdminController
{
    protected $title = 'Agent管理';

    public function index(Content $content)
    {
        return $content
            ->title($this->title)
            ->description('管理您的Agent')
            ->body($this->grid());
    }

    protected function grid()
    {
        $grid = new Grid(new Agent());

        // 加载关联关系
        $grid->model()->with(['project']);

        // 只显示当前用户的Agent
        $user = $this->getCurrentUser();
        if ($user) {
            $grid->model()->where('user_id', $user->id);
        }

        $grid->column('id', 'ID')->sortable();
        $grid->column('identifier', 'Agent ID')->limit(30);
        $grid->column('name', 'Agent名称')->limit(30);
        $grid->column('type', '类型')->using([
            'claude' => 'Claude',
            'gpt' => 'GPT',
            'custom' => '自定义'
        ])->label([
            'claude' => 'primary',
            'gpt' => 'success',
            'custom' => 'info'
        ]);

        $grid->column('status', '状态')->using([
            'active' => '活跃',
            'inactive' => '非活跃',
            'suspended' => '已暂停'
        ])->label([
            'active' => 'success',
            'inactive' => 'warning',
            'suspended' => 'danger'
        ]);

        $grid->column('last_active_at', '最后活跃时间')->sortable();
        $grid->column('created_at', '注册时间')->sortable();

        // 简化统计信息
        $grid->column('tasks_count', '处理任务数')->display(function () {
            return '0'; // 后续完善
        });

        $grid->column('project.name', '所属项目');

        $grid->filter(function($filter) {
            $filter->like('name', 'Agent名称');
            $filter->like('identifier', 'Agent ID');
            $filter->equal('status', '状态')->select([
                'active' => '活跃',
                'inactive' => '非活跃',
                'suspended' => '已暂停'
            ]);
            $filter->equal('type', '类型')->select([
                'claude' => 'Claude',
                'gpt' => 'GPT',
                'custom' => '自定义'
            ]);
        });

        $grid->actions(function ($actions) {
            // 暂时移除自定义操作，避免类不存在错误
            // $actions->add(new \App\UserAdmin\Actions\TestAgentAction());
            // $actions->add(new \App\UserAdmin\Actions\ViewAgentLogsAction());
        });

        return $grid;
    }

    protected function form()
    {
        $form = new Form(new Agent());

        $form->text('identifier', 'Agent ID')->required()->help('唯一标识符');
        $form->text('name', 'Agent名称')->required();
        $form->textarea('description', '描述');

        // 项目选择 - 只显示当前用户的项目
        $user = auth('user-admin')->user();
        $projects = $user ? \Modules\Project\Models\Project::where('user_id', $user->id)->pluck('name', 'id') : [];
        $form->select('project_id', '所属项目')->options($projects)->required()->help('选择此Agent所属的项目');

        $form->select('status', '状态')->options([
            'active' => '活跃',
            'inactive' => '非活跃',
            'suspended' => '已暂停'
        ])->default('active');



        $form->textarea('configuration', '配置')->help('JSON格式的Agent配置')->default('{}');
        $form->textarea('capabilities', '能力描述')->help('JSON格式的能力描述')->default('{}');

        // 保存时设置用户关联
        $form->saving(function (Form $form) {
            $user = auth('user-admin')->user();
            if ($user && !$form->model()->id) {
                $form->model()->user_id = $user->id;
            }
        });

        return $form;
    }

    protected function detail($id)
    {
        $show = new Show(Agent::findOrFail($id));

        $show->field('id', 'ID');
        $show->field('identifier', 'Agent ID');
        $show->field('name', 'Agent名称');
        $show->field('description', '描述');
        $show->field('type', '类型');
        $show->field('status', '状态');
        $show->field('last_active_at', '最后活跃时间');
        $show->field('created_at', '注册时间');
        $show->field('updated_at', '更新时间');

        // 简化统计信息
        $show->field('stats', '统计信息')->as(function () {
            return [
                '处理任务总数' => 0, // 后续完善
                '已完成任务' => 0, // 后续完善
                '参与项目数' => 0, // 后续完善
                '平均响应时间' => '2.3秒' // 模拟数据
            ];
        })->as(function ($stats) {
            $html = '<ul>';
            foreach ($stats as $key => $value) {
                $html .= "<li><strong>{$key}:</strong> {$value}</li>";
            }
            $html .= '</ul>';
            return $html;
        });

        $show->field('allowed_projects', '允许访问的项目')->as(function ($projectIds) {
            if (empty($projectIds)) return '无';
            return Project::whereIn('id', $projectIds)->pluck('name')->implode(', ');
        });

        $show->field('allowed_actions', '允许的操作')->as(function ($actions) {
            if (empty($actions)) return '无';
            $actionLabels = [
                'read' => '读取',
                'create_task' => '创建任务',
                'update_task' => '更新任务',
                'claim_task' => '认领任务',
                'complete_task' => '完成任务'
            ];
            return collect($actions)->map(function($action) use ($actionLabels) {
                return $actionLabels[$action] ?? $action;
            })->implode(', ');
        });

        $show->field('config', '配置')->as(function ($value) {
            return '<pre>' . json_encode(json_decode($value), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . '</pre>';
        });
        $show->field('capabilities', '能力描述')->as(function ($value) {
            return '<pre>' . json_encode(json_decode($value), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . '</pre>';
        });

        return $show;
    }

    protected function getCurrentUser()
    {
        $userAdminUser = auth('user-admin')->user();
        return User::where('name', $userAdminUser->name)->first();
    }
}
