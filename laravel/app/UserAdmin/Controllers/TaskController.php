<?php

namespace App\UserAdmin\Controllers;

use Dcat\Admin\Http\Controllers\AdminController;
use Dcat\Admin\Grid;
use Dcat\Admin\Form;
use Dcat\Admin\Show;
use Dcat\Admin\Layout\Content;
use App\Modules\Task\Models\Task;
use App\Modules\Project\Models\Project;
use App\Modules\User\Models\User;

class TaskController extends AdminController
{
    protected $title = '任务管理';

    public function index(Content $content)
    {
        return $content
            ->title($this->title)
            ->description('管理您的任务')
            ->body($this->grid());
    }

    protected function grid()
    {
        $grid = new Grid(new Task());

        // 只显示当前用户的任务
        $user = $this->getCurrentUser();
        if ($user) {
            // 通过项目关联限制只显示用户自己的任务
            $userProjectIds = Project::where('user_id', $user->id)->pluck('id');
            $grid->model()->whereIn('project_id', $userProjectIds);
        } else {
            // 如果无法获取用户，不显示任何任务
            $grid->model()->where('id', -1);
        }

        $grid->column('id', 'ID')->sortable();
        $grid->column('title', '任务标题')->limit(40);
        $grid->column('project.name', '所属项目')->limit(20);

        $grid->column('type', '任务类型')->using([
            'main' => '主任务',
            'sub' => '子任务'
        ])->label([
            'main' => 'primary',
            'sub' => 'info'
        ]);

        $grid->column('status', '状态')->using([
            'pending' => '待处理',
            'in_progress' => '进行中',
            'completed' => '已完成',
            'cancelled' => '已取消'
        ])->label([
            'pending' => 'warning',
            'in_progress' => 'info',
            'completed' => 'success',
            'cancelled' => 'danger'
        ]);

        $grid->column('priority', '优先级')->using([
            'low' => '低',
            'medium' => '中',
            'high' => '高',
            'urgent' => '紧急'
        ])->label([
            'low' => 'default',
            'medium' => 'info',
            'high' => 'warning',
            'urgent' => 'danger'
        ]);

        $grid->column('assigned_to', '分配给')->display(function ($userId) {
            if ($userId) {
                $user = User::find($userId);
                return $user ? $user->name : '未知用户';
            }
            return '未分配';
        });

        $grid->column('due_date', '截止日期')->sortable();
        $grid->column('created_at', '创建时间')->sortable();

        // 进度显示
        $grid->column('progress', '进度')->progressBar();

        $grid->filter(function($filter) {
            $filter->like('title', '任务标题');
            $filter->equal('status', '状态')->select([
                'pending' => '待处理',
                'in_progress' => '进行中',
                'completed' => '已完成',
                'cancelled' => '已取消'
            ]);
            $filter->equal('priority', '优先级')->select([
                'low' => '低',
                'medium' => '中',
                'high' => '高',
                'urgent' => '紧急'
            ]);
            // 只显示当前用户的项目
            $user = $this->getCurrentUser();
            $userProjects = $user ?
                Project::where('user_id', $user->id)->pluck('name', 'id')->toArray() :
                [];
            $filter->equal('project_id', '项目')->select($userProjects);
        });

        return $grid;
    }

    protected function form()
    {
        $form = new Form(new Task());

        $form->text('title', '任务标题')->required();
        $form->textarea('description', '任务描述');

        $user = $this->getCurrentUser();
        // 只显示当前用户的项目
        $userProjects = $user ?
            Project::where('user_id', $user->id)->pluck('name', 'id')->toArray() :
            [];

        $form->select('project_id', '所属项目')->options($userProjects)->required()
             ->help('只能选择您自己的项目');

        $form->select('type', '任务类型')->options([
            'main' => '主任务',
            'sub' => '子任务'
        ])->default('main');

        $form->select('status', '状态')->options([
            'pending' => '待处理',
            'in_progress' => '进行中',
            'completed' => '已完成',
            'cancelled' => '已取消'
        ])->default('pending');

        $form->select('priority', '优先级')->options([
            'low' => '低',
            'medium' => '中',
            'high' => '高',
            'urgent' => '紧急'
        ])->default('medium');

        $form->select('assigned_to', '分配给')->options(
            User::pluck('name', 'id')->toArray()
        );

        $form->date('due_date', '截止日期');
        $form->number('progress', '进度')->min(0)->max(100)->default(0);
        $form->textarea('metadata', '元数据')->help('JSON格式的任务元数据')->default('{}');

        // 保存时验证项目归属
        $form->saving(function (Form $form) {
            $user = auth('user-admin')->user();
            if (!$user) {
                throw new \Exception('无法获取当前用户信息');
            }

            // 验证项目是否属于当前用户
            if ($form->project_id) {
                $project = Project::find($form->project_id);
                if (!$project || $project->user_id !== $user->id) {
                    throw new \Exception('您没有权限访问该项目');
                }
            }

            // 设置创建者
            if (!$form->model()->id) {
                $form->created_by = $user->id;
            }
        });

        return $form;
    }

    protected function detail($id)
    {
        $show = new Show(Task::findOrFail($id));

        $show->field('id', 'ID');
        $show->field('title', '任务标题');
        $show->field('description', '任务描述');
        $show->field('project.name', '所属项目');
        $show->field('type', '任务类型');
        $show->field('status', '状态');
        $show->field('priority', '优先级');
        $show->field('progress', '进度')->as(function ($progress) {
            return $progress . '%';
        });
        $show->field('assigned_to', '分配给')->as(function ($userId) {
            if ($userId) {
                $user = User::find($userId);
                return $user ? $user->name : '未知用户';
            }
            return '未分配';
        });
        $show->field('due_date', '截止日期');
        $show->field('created_at', '创建时间');
        $show->field('updated_at', '更新时间');

        return $show;
    }

    protected function getCurrentUser()
    {
        $userAdminUser = auth('user-admin')->user();
        if (!$userAdminUser) {
            return null;
        }

        // 直接返回认证的用户，因为user-admin guard使用的就是User模型
        return $userAdminUser;
    }
}
