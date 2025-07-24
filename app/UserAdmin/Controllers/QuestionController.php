<?php

namespace App\UserAdmin\Controllers;

use App\Modules\Agent\Models\AgentQuestion;
use App\Modules\Agent\Models\Agent;
use App\Modules\Task\Models\Task;

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

            // 只显示当前用户的问题，默认不显示已忽略的问题
            $user = $this->getCurrentUser();
            if ($user) {
                $grid->model()->where('user_id', $user->id);
            }

            // 根据请求参数决定是否显示已忽略的问题
            if (!request()->has('status') || request('status') !== '') {
                // 默认不显示已忽略的问题（包括过期自动忽略的问题）
                $grid->model()->where('status', '!=', AgentQuestion::STATUS_IGNORED);
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
            $grid->column('expires_at', '过期时间')->display(function ($value, $column, $model) {
                if (!$value) return '-';
                $expiresAt = \Carbon\Carbon::parse($value);
                if ($expiresAt->isPast()) {
                    // 如果已过期且状态仍为待回答，说明可能需要手动处理
                    if ($model->status === AgentQuestion::STATUS_PENDING) {
                        return '<span class="text-danger"><strong>' . $expiresAt->format('Y-m-d H:i') . ' (已过期，待处理)</strong></span>';
                    }
                    return '<span class="text-danger">' . $expiresAt->format('Y-m-d H:i') . ' (已过期)</span>';
                }
                // 即将过期的警告（30分钟内）
                if ($expiresAt->diffInMinutes(now()) <= 30) {
                    return '<span class="text-warning">' . $expiresAt->format('Y-m-d H:i') . ' (即将过期)</span>';
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
                if ($actions->row->status === AgentQuestion::STATUS_PENDING) {
                    $actions->append(new \App\UserAdmin\Actions\Question\AnswerQuestionAction());
                    $actions->append(new \App\UserAdmin\Actions\Question\IgnoreQuestionAction());
                }
            });

            // 工具栏
            $grid->tools(function (Grid\Tools $tools) {
                $tools->append('<a href="' . admin_url('questions/pending') . '" class="btn btn-sm btn-warning">
                <i class="fa fa-clock-o"></i> 待回答问题
            </a>');
                $tools->append('<a href="' . admin_url('questions?status=') . '" class="btn btn-sm btn-info">
                <i class="fa fa-list"></i> 显示所有问题
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

        // 问题类型字段已移除，跳过显示

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
    public function answer($id, Content $content, Request $request)
    {
        $question = AgentQuestion::findOrFail($id);

        // 权限检查
        $user = $this->getCurrentUser();
        if ($user && $question->user_id !== $user->id) {
            abort(403, '无权访问此问题');
        }

        if ($question->status !== AgentQuestion::STATUS_PENDING) {
            if ($request->isMethod('post')) {
                return response()->json(['error' => '此问题已经处理过了'], 400);
            }
            return redirect()->back()->with('error', '此问题已经处理过了');
        }

        // 处理POST请求（回答问题）
        if ($request->isMethod('post')) {
            $validated = $request->validate([
                'answer' => 'required|string',
                'answer_type' => 'sometimes|string|in:TEXT',
            ]);

            // 更新问题
            $question->answer = $validated['answer'];
            $question->answer_type = $validated['answer_type'] ?? AgentQuestion::ANSWER_TYPE_TEXT;
            $question->answered_by = $user->id;
            $question->answered_at = now();
            $question->status = AgentQuestion::STATUS_ANSWERED;
            $question->save();

            return response()->json(['success' => true, 'message' => '问题回答成功']);
        }

        // 处理GET请求（显示回答表单页面）
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
     * 待回答问题列表
     */
    public function pending(Content $content)
    {
        return $content
            ->title('待回答问题')
            ->description('需要您回答的问题列表')
            ->body($this->pendingGrid());
    }

    /**
     * 待回答问题表格
     */
    private function pendingGrid()
    {
        return Grid::make(new AgentQuestion(), function (Grid $grid) {
            // 加载关联关系
            $grid->model()->with(['agent', 'task', 'answeredBy']);

            // 只显示当前用户的待回答问题
            $user = $this->getCurrentUser();
            if ($user) {
                $grid->model()->where('user_id', $user->id)
                    ->where('status', AgentQuestion::STATUS_PENDING);
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

            $grid->column('created_at', '创建时间')->sortable();
            $grid->column('expires_at', '过期时间')->sortable();

            // 操作列
            $grid->actions(function (Grid\Displayers\Actions $actions) {
                $actions->disableDelete();
                $actions->disableEdit();
                $actions->disableView();

                // 添加回答和忽略按钮
                $actions->append(new \App\UserAdmin\Actions\Question\AnswerQuestionAction());
                $actions->append(new \App\UserAdmin\Actions\Question\IgnoreQuestionAction());
            });

            // 工具栏
            $grid->tools(function (Grid\Tools $tools) {
                $tools->append('<a href="' . admin_url('questions') . '" class="btn btn-sm btn-primary">
                <i class="fa fa-list"></i> 所有问题
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

            $grid->disableCreateButton();
            $grid->disableExport();
        });
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
