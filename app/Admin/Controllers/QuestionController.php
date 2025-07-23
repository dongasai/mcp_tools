<?php

namespace App\Admin\Controllers;

use App\Modules\Agent\Models\AgentQuestion;
use App\Modules\Agent\Models\Agent;
use App\Modules\User\Models\User;
use App\Modules\Project\Models\Project;
use App\Modules\Task\Models\Task;
use Dcat\Admin\Form;
use Dcat\Admin\Grid;
use Dcat\Admin\Show;
use Dcat\Admin\Http\Controllers\AdminController;
use Dcat\Admin\Layout\Content;
use Illuminate\Http\Request;

class QuestionController extends AdminController
{
    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        return Grid::make(AgentQuestion::with(['agent', 'user', 'task', 'project', 'answeredBy']), function (Grid $grid) {
            $grid->column('id')->sortable();
            $grid->column('title', '问题标题')->limit(50);
            $grid->column('agent.name', 'Agent名称');
            $grid->column('user.name', '提问给');
            $grid->column('project.name', '所属项目');
            $grid->column('task.title', '关联任务')->limit(30);
            
            $grid->column('priority', '优先级')->using([
                AgentQuestion::PRIORITY_URGENT => '紧急',
                AgentQuestion::PRIORITY_HIGH => '高',
                AgentQuestion::PRIORITY_MEDIUM => '中',
                AgentQuestion::PRIORITY_LOW => '低',
            ])->label([
                AgentQuestion::PRIORITY_URGENT => 'danger',
                AgentQuestion::PRIORITY_HIGH => 'warning',
                AgentQuestion::PRIORITY_MEDIUM => 'primary',
                AgentQuestion::PRIORITY_LOW => 'default',
            ]);
            
            $grid->column('status', '状态')->using([
                AgentQuestion::STATUS_PENDING => '待回答',
                AgentQuestion::STATUS_ANSWERED => '已回答',
                AgentQuestion::STATUS_IGNORED => '已忽略',
            ])->label([
                AgentQuestion::STATUS_PENDING => 'warning',
                AgentQuestion::STATUS_ANSWERED => 'success',
                AgentQuestion::STATUS_IGNORED => 'default',
            ]);
            
            $grid->column('answered_at', '回答时间');
            $grid->column('expires_at', '过期时间');
            $grid->column('created_at', '创建时间')->sortable();

            // 过滤器
            $grid->filter(function (Grid\Filter $filter) {
                $filter->equal('id');
                $filter->like('title', '问题标题');
                $filter->equal('agent_id', 'Agent')->select(Agent::pluck('name', 'id'));
                $filter->equal('user_id', '提问给')->select(User::pluck('name', 'id'));
                $filter->equal('project_id', '项目')->select(Project::pluck('name', 'id'));
                $filter->equal('task_id', '任务')->select(Task::pluck('title', 'id'));
                
                $filter->equal('priority', '优先级')->select([
                    AgentQuestion::PRIORITY_URGENT => '紧急',
                    AgentQuestion::PRIORITY_HIGH => '高',
                    AgentQuestion::PRIORITY_MEDIUM => '中',
                    AgentQuestion::PRIORITY_LOW => '低',
                ]);
                
                $filter->equal('status', '状态')->select([
                    AgentQuestion::STATUS_PENDING => '待回答',
                    AgentQuestion::STATUS_ANSWERED => '已回答',
                    AgentQuestion::STATUS_IGNORED => '已忽略',
                ]);
                
                $filter->between('created_at', '创建时间')->datetime();
                $filter->between('expires_at', '过期时间')->datetime();
            });

            // 批量操作
            $grid->batchActions(function (Grid\Tools\BatchActions $batch) {
                $batch->add(new \App\Admin\Actions\Grid\BatchIgnoreQuestions());
            });

            // 工具栏
            $grid->tools(function (Grid\Tools $tools) {
                $tools->append('<a href="' . admin_url('questions/stats') . '" class="btn btn-sm btn-primary">
                    <i class="fa fa-bar-chart"></i> 统计分析
                </a>');
            });

            // 默认排序
            $grid->model()->orderBy('created_at', 'desc');
        });
    }

    /**
     * Make a show builder.
     *
     * @param mixed $id
     * @return Show
     */
    protected function detail($id)
    {
        return Show::make($id, AgentQuestion::with(['agent', 'user', 'task', 'project', 'answeredBy']), function (Show $show) {
            $show->field('id');
            $show->field('title', '问题标题');
            $show->field('content', '问题内容')->unescape();
            
            $show->field('agent.name', 'Agent名称');
            $show->field('user.name', '提问给');
            $show->field('project.name', '所属项目');
            $show->field('task.title', '关联任务');
            
            $show->field('priority', '优先级')->using([
                AgentQuestion::PRIORITY_URGENT => '紧急',
                AgentQuestion::PRIORITY_HIGH => '高',
                AgentQuestion::PRIORITY_MEDIUM => '中',
                AgentQuestion::PRIORITY_LOW => '低',
            ]);
            
            $show->field('status', '状态')->using([
                AgentQuestion::STATUS_PENDING => '待回答',
                AgentQuestion::STATUS_ANSWERED => '已回答',
                AgentQuestion::STATUS_IGNORED => '已忽略',
            ]);
            
            $show->field('context', '上下文')->json();
            $show->field('answer_options', '可选答案')->json();
            $show->field('answer', '回答内容')->unescape();
            $show->field('answer_type', '回答类型');
            $show->field('answeredBy.name', '回答者');
            $show->field('answered_at', '回答时间');
            $show->field('expires_at', '过期时间');
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
        return Form::make(AgentQuestion::query(), function (Form $form) {
            $form->display('id');
            
            $form->text('title', '问题标题')->required();
            $form->textarea('content', '问题内容')->required();
            
            $form->select('agent_id', 'Agent')->options(Agent::pluck('name', 'id'))->required();
            $form->select('user_id', '提问给')->options(User::pluck('name', 'id'))->required();
            $form->select('project_id', '所属项目')->options(Project::pluck('name', 'id'));
            $form->select('task_id', '关联任务')->options(Task::pluck('title', 'id'));
            
            // 问题类型已移除，默认为文本问题
            
            $form->select('priority', '优先级')->options([
                AgentQuestion::PRIORITY_URGENT => '紧急',
                AgentQuestion::PRIORITY_HIGH => '高',
                AgentQuestion::PRIORITY_MEDIUM => '中',
                AgentQuestion::PRIORITY_LOW => '低',
            ])->default(AgentQuestion::PRIORITY_MEDIUM)->required();
            
            $form->select('status', '状态')->options([
                AgentQuestion::STATUS_PENDING => '待回答',
                AgentQuestion::STATUS_ANSWERED => '已回答',
                AgentQuestion::STATUS_IGNORED => '已忽略',
            ])->default(AgentQuestion::STATUS_PENDING)->required();
            
            $form->textarea('context', '上下文')->placeholder('JSON格式的上下文信息');
            $form->textarea('answer_options', '可选答案')->placeholder('JSON格式的可选答案列表');
            
            $form->textarea('answer', '回答内容');
            $form->select('answer_type', '回答类型')->options([
                AgentQuestion::ANSWER_TYPE_TEXT => '文本',
            ]);
            
            $form->select('answered_by', '回答者')->options(User::pluck('name', 'id'));
            $form->datetime('answered_at', '回答时间');
            $form->datetime('expires_at', '过期时间');
            
            $form->display('created_at', '创建时间');
            $form->display('updated_at', '更新时间');
        });
    }

    /**
     * 统计分析页面
     */
    public function stats(Content $content)
    {
        return $content
            ->title('问题统计分析')
            ->description('Agent问题管理统计')
            ->body($this->getStatsView());
    }

    /**
     * 获取统计视图
     */
    private function getStatsView()
    {
        $stats = [
            'total' => AgentQuestion::count(),
            'pending' => AgentQuestion::where('status', AgentQuestion::STATUS_PENDING)->count(),
            'answered' => AgentQuestion::where('status', AgentQuestion::STATUS_ANSWERED)->count(),
            'ignored' => AgentQuestion::where('status', AgentQuestion::STATUS_IGNORED)->count(),
            'urgent' => AgentQuestion::where('priority', AgentQuestion::PRIORITY_URGENT)->count(),
            'high' => AgentQuestion::where('priority', AgentQuestion::PRIORITY_HIGH)->count(),
            'expired' => AgentQuestion::where('expires_at', '<', now())->where('status', AgentQuestion::STATUS_PENDING)->count(),
        ];

        return view('admin.questions.stats', compact('stats'));
    }
}
