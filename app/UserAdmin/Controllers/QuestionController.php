<?php

namespace App\UserAdmin\Controllers;

use App\Modules\Agent\Models\AgentQuestion;
use App\Modules\Agent\Models\Agent;
use App\Models\Task;

use Dcat\Admin\Http\Controllers\AdminController;
use Dcat\Admin\Grid;
use Dcat\Admin\Form;
use Dcat\Admin\Show;
use Dcat\Admin\Layout\Content;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class QuestionController extends AdminController
{
    protected $title = '问题管理';





    protected function grid()
    {
        return Grid::make(new AgentQuestion(), function (Grid $grid) {

            // 加载关联关系
            $grid->model()->with(['agent', 'task', 'answeredBy']);

            // 只显示当前用户的问题
            $user = $this->getCurrentUser();
            if ($user) {
                $grid->model()->where('user_id', $user->id);
            }

            $grid->column('id', 'ID')->sortable();
            $grid->column('title', '问题标题')->limit(50);
            $grid->column('agent.name', 'Agent名称');
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
            $grid->column('expires_at', '过期时间')->display(function ($value) {
                if (!$value) return '-';
                $expiresAt = \Carbon\Carbon::parse($value);
                if ($expiresAt->isPast() && $this->status === AgentQuestion::STATUS_PENDING) {
                    return '<span class="text-danger">' . $expiresAt->format('Y-m-d H:i') . ' (已过期)</span>';
                }
                return $expiresAt->format('Y-m-d H:i');
            });
            $grid->column('created_at', '创建时间')->sortable();

            // 过滤器
            $grid->filter(function (Grid\Filter $filter) use ($user) {
                $filter->like('title', '问题标题');

                if ($user) {
                    $filter->equal('agent_id', 'Agent')->select(
                        Agent::where('user_id', $user->id)->pluck('name', 'id')
                    );
                    $filter->equal('task_id', '任务')->select(
                        Task::where('user_id', $user->id)->pluck('title', 'id')
                    );
                }

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
            });

            // 操作列
            $grid->actions(function (Grid\Displayers\Actions $actions) {
                $actions->disableDelete();
                $actions->disableEdit();

                // 只有待回答的问题才能回答
                if ($this->status === AgentQuestion::STATUS_PENDING) {
                    $actions->append(new \App\UserAdmin\Actions\Grid\AnswerQuestionAction());
                    $actions->append(new \App\UserAdmin\Actions\Grid\IgnoreQuestionAction());
                }
            });

            // 工具栏
            $grid->tools(function (Grid\Tools $tools) {
                $tools->append('<a href="' . admin_url('user-admin/questions/pending') . '" class="btn btn-sm btn-warning">
                <i class="fa fa-clock-o"></i> 待回答问题
            </a>');
            });

            // 默认排序：优先级高的在前，创建时间新的在前
            $grid->model()->orderByRaw("
            CASE priority
                WHEN 'URGENT' THEN 1
                WHEN 'HIGH' THEN 2
                WHEN 'MEDIUM' THEN 3
                WHEN 'LOW' THEN 4
            END, created_at DESC
        ");

            // 禁用创建按钮（问题只能由Agent创建）
            $grid->disableCreateButton();

            return $grid;
        });


    }

    protected function detail($id)
    {
        $show = new Show(AgentQuestion::findOrFail($id));

        // 权限检查：只能查看自己的问题
        $user = $this->getCurrentUser();
        if ($user && $show->model()->user_id !== $user->id) {
            abort(403, '无权访问此问题');
        }

        $show->field('id', 'ID');
        $show->field('title', '问题标题');
        $show->field('content', '问题内容')->unescape();

        $show->field('agent.name', 'Agent名称');
        $show->field('task.title', '关联任务');

        // 问题类型已移除，默认为文本问题

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
        $show->field('answeredBy.name', '回答者');
        $show->field('answered_at', '回答时间');
        $show->field('expires_at', '过期时间');
        $show->field('created_at', '创建时间');

        return $show;
    }

    protected function form()
    {
        $form = new Form(new AgentQuestion());

        $form->display('id', 'ID');
        $form->display('title', '问题标题');
        $form->display('content', '问题内容');
        $form->display('agent.name', 'Agent名称');

        $form->textarea('answer', '回答内容')->required()->rows(5);
        $form->select('answer_type', '回答类型')->options([
            AgentQuestion::ANSWER_TYPE_TEXT => '文本',
        ])->default(AgentQuestion::ANSWER_TYPE_TEXT);

        // 保存时自动设置回答者和回答时间
        $form->saving(function (Form $form) {
            $user = Auth::guard('user-admin')->user();
            if ($user) {
                $form->model()->answered_by = $user->id;
                $form->model()->answered_at = now();
                $form->model()->status = AgentQuestion::STATUS_ANSWERED;
            }
        });

        return $form;
    }

    /**
     * 回答问题页面
     */
    public function answer($id, Content $content)
    {
        $question = AgentQuestion::findOrFail($id);

        // 权限检查
        $user = $this->getCurrentUser();
        if ($user && $question->user_id !== $user->id) {
            abort(403, '无权访问此问题');
        }

        if ($question->status !== AgentQuestion::STATUS_PENDING) {
            return redirect()->back()->with('error', '此问题已经处理过了');
        }

        return $content
            ->title('回答问题')
            ->description($question->title)
            ->body($this->answerForm($question));
    }

    /**
     * 忽略问题
     */
    public function ignore($id)
    {
        $question = AgentQuestion::findOrFail($id);

        // 权限检查
        $user = $this->getCurrentUser();
        if ($user && $question->user_id !== $user->id) {
            abort(403, '无权访问此问题');
        }

        if ($question->status !== AgentQuestion::STATUS_PENDING) {
            return redirect()->back()->with('error', '此问题已经处理过了');
        }

        $question->markAsIgnored();

        return redirect()->back()->with('success', '问题已忽略');
    }

    /**
     * 获取当前用户
     */
    private function getCurrentUser()
    {
        return Auth::guard('user-admin')->user();
    }

    /**
     * 回答表单
     */
    private function answerForm($question)
    {
        $form = new Form(new AgentQuestion());
        $form->model()->fill($question->toArray());

        $form->display('title', '问题标题');
        $form->display('content', '问题内容')->unescape();

        if ($question->answer_options) {
            $form->radio('answer', '选择答案')->options(
                collect($question->answer_options)->pluck('label', 'value')->toArray()
            )->required();
        } else {
            $form->textarea('answer', '回答内容')->required()->rows(5);
        }

        $form->hidden('id')->value($question->id);

        $form->saving(function (Form $form) {
            $user = Auth::guard('user-admin')->user();
            if ($user) {
                $form->model()->answered_by = $user->id;
                $form->model()->answered_at = now();
                $form->model()->status = AgentQuestion::STATUS_ANSWERED;
            }
        });

        return $form;
    }
}
