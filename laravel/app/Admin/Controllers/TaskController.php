<?php

namespace App\Admin\Controllers;

use App\Modules\Task\Models\Task;
use App\Modules\User\Models\User;
use App\Modules\Agent\Models\Agent;
use App\Modules\Project\Models\Project;
use Dcat\Admin\Form;
use Dcat\Admin\Grid;
use Dcat\Admin\Show;
use Dcat\Admin\Http\Controllers\AdminController;

class TaskController extends AdminController
{
    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        return Grid::make(Task::with(['user', 'agent', 'project', 'parentTask']), function (Grid $grid) {
            $grid->column('id')->sortable();
            $grid->column('title', '任务标题');
            $grid->column('type', '类型')->using([
                Task::TYPE_MAIN => '主任务',
                Task::TYPE_SUB => '子任务',
                Task::TYPE_MILESTONE => '里程碑',
                Task::TYPE_BUG => '错误修复',
                Task::TYPE_FEATURE => '新功能',
                Task::TYPE_IMPROVEMENT => '改进',
            ])->label([
                Task::TYPE_MAIN => 'primary',
                Task::TYPE_SUB => 'info',
                Task::TYPE_MILESTONE => 'warning',
                Task::TYPE_BUG => 'danger',
                Task::TYPE_FEATURE => 'success',
                Task::TYPE_IMPROVEMENT => 'default',
            ]);
            $grid->column('status', '状态')->using([
                Task::STATUS_PENDING => '待处理',
                Task::STATUS_IN_PROGRESS => '进行中',
                Task::STATUS_COMPLETED => '已完成',
                Task::STATUS_BLOCKED => '已阻塞',
                Task::STATUS_CANCELLED => '已取消',
                Task::STATUS_ON_HOLD => '暂停',
            ])->label([
                Task::STATUS_PENDING => 'default',
                Task::STATUS_IN_PROGRESS => 'info',
                Task::STATUS_COMPLETED => 'success',
                Task::STATUS_BLOCKED => 'danger',
                Task::STATUS_CANCELLED => 'secondary',
                Task::STATUS_ON_HOLD => 'warning',
            ]);
            $grid->column('priority', '优先级')->using([
                Task::PRIORITY_LOW => '低',
                Task::PRIORITY_MEDIUM => '中',
                Task::PRIORITY_HIGH => '高',
                Task::PRIORITY_URGENT => '紧急',
            ])->label([
                Task::PRIORITY_LOW => 'default',
                Task::PRIORITY_MEDIUM => 'info',
                Task::PRIORITY_HIGH => 'warning',
                Task::PRIORITY_URGENT => 'danger',
            ]);
            $grid->column('user.name', '用户');
            $grid->column('agent.name', 'Agent');
            $grid->column('project.name', '项目');
            $grid->column('parentTask.title', '父任务');
            $grid->column('progress', '进度')->progressBar();
            $grid->column('due_date', '截止时间');
            $grid->column('created_at', '创建时间');
            $grid->column('updated_at', '更新时间')->sortable();

            $grid->filter(function (Grid\Filter $filter) {
                $filter->equal('id');
                $filter->like('title', '任务标题');
                $filter->equal('type', '类型')->select([
                    Task::TYPE_MAIN => '主任务',
                    Task::TYPE_SUB => '子任务',
                    Task::TYPE_MILESTONE => '里程碑',
                    Task::TYPE_BUG => '错误修复',
                    Task::TYPE_FEATURE => '新功能',
                    Task::TYPE_IMPROVEMENT => '改进',
                ]);
                $filter->equal('status', '状态')->select([
                    Task::STATUS_PENDING => '待处理',
                    Task::STATUS_IN_PROGRESS => '进行中',
                    Task::STATUS_COMPLETED => '已完成',
                    Task::STATUS_BLOCKED => '已阻塞',
                    Task::STATUS_CANCELLED => '已取消',
                    Task::STATUS_ON_HOLD => '暂停',
                ]);
                $filter->equal('priority', '优先级')->select([
                    Task::PRIORITY_LOW => '低',
                    Task::PRIORITY_MEDIUM => '中',
                    Task::PRIORITY_HIGH => '高',
                    Task::PRIORITY_URGENT => '紧急',
                ]);
                $filter->equal('user_id', '用户')->select(User::pluck('name', 'id'));
                $filter->equal('agent_id', 'Agent')->select(Agent::pluck('name', 'id'));
                $filter->equal('project_id', '项目')->select(Project::pluck('name', 'id'));
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
        return Show::make($id, Task::with(['user', 'agent', 'project', 'parentTask', 'subTasks']), function (Show $show) {
            $show->field('id');
            $show->field('title', '任务标题');
            $show->field('description', '任务描述');
            $show->field('type', '类型')->using([
                Task::TYPE_MAIN => '主任务',
                Task::TYPE_SUB => '子任务',
                Task::TYPE_MILESTONE => '里程碑',
                Task::TYPE_BUG => '错误修复',
                Task::TYPE_FEATURE => '新功能',
                Task::TYPE_IMPROVEMENT => '改进',
            ]);
            $show->field('status', '状态')->using([
                Task::STATUS_PENDING => '待处理',
                Task::STATUS_IN_PROGRESS => '进行中',
                Task::STATUS_COMPLETED => '已完成',
                Task::STATUS_BLOCKED => '已阻塞',
                Task::STATUS_CANCELLED => '已取消',
                Task::STATUS_ON_HOLD => '暂停',
            ]);
            $show->field('priority', '优先级')->using([
                Task::PRIORITY_LOW => '低',
                Task::PRIORITY_MEDIUM => '中',
                Task::PRIORITY_HIGH => '高',
                Task::PRIORITY_URGENT => '紧急',
            ]);
            $show->field('user.name', '用户');
            $show->field('agent.name', 'Agent');
            $show->field('project.name', '项目');
            $show->field('parentTask.title', '父任务');
            $show->field('progress', '进度');
            $show->field('due_date', '截止时间');
            $show->field('estimated_hours', '预估工时');
            $show->field('actual_hours', '实际工时');
            $show->field('tags', '标签')->json();
            $show->field('metadata', '元数据')->json();
            $show->field('result', '结果')->json();
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
        return Form::make(Task::query(), function (Form $form) {
            $form->display('id');
            $form->text('title', '任务标题')->required();
            $form->textarea('description', '任务描述');
            $form->select('type', '类型')->options([
                Task::TYPE_MAIN => '主任务',
                Task::TYPE_SUB => '子任务',
                Task::TYPE_MILESTONE => '里程碑',
                Task::TYPE_BUG => '错误修复',
                Task::TYPE_FEATURE => '新功能',
                Task::TYPE_IMPROVEMENT => '改进',
            ])->default(Task::TYPE_MAIN)->required();
            $form->select('status', '状态')->options([
                Task::STATUS_PENDING => '待处理',
                Task::STATUS_IN_PROGRESS => '进行中',
                Task::STATUS_COMPLETED => '已完成',
                Task::STATUS_BLOCKED => '已阻塞',
                Task::STATUS_CANCELLED => '已取消',
                Task::STATUS_ON_HOLD => '暂停',
            ])->default(Task::STATUS_PENDING)->required();
            $form->select('priority', '优先级')->options([
                Task::PRIORITY_LOW => '低',
                Task::PRIORITY_MEDIUM => '中',
                Task::PRIORITY_HIGH => '高',
                Task::PRIORITY_URGENT => '紧急',
            ])->default(Task::PRIORITY_MEDIUM)->required();
            $form->select('user_id', '用户')->options(User::pluck('name', 'id'))->required();
            $form->select('agent_id', 'Agent')->options(Agent::pluck('name', 'id'));
            $form->select('project_id', '项目')->options(Project::pluck('name', 'id'));
            $form->select('parent_task_id', '父任务')->options(Task::mainTasks()->pluck('title', 'id'));
            $form->number('progress', '进度')->min(0)->max(100)->default(0);
            $form->datetime('due_date', '截止时间');
            $form->decimal('estimated_hours', '预估工时')->min(0);
            $form->decimal('actual_hours', '实际工时')->min(0);
            $form->tags('tags', '标签');
            $form->json('metadata', '元数据');
            $form->json('result', '结果');

            $form->display('created_at', '创建时间');
            $form->display('updated_at', '更新时间');
        });
    }
}
