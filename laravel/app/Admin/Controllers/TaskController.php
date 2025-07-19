<?php

namespace App\Admin\Controllers;

use App\Modules\Task\Models\Task;
use App\Modules\Task\Models\TaskComment;
use App\Modules\User\Models\User;
use App\Modules\Agent\Models\Agent;
use App\Modules\Project\Models\Project;
use App\Modules\Task\Enums\TASKTYPE;
use App\Modules\Task\Enums\TASKSTATUS;
use App\Modules\Task\Enums\TASKPRIORITY;
use App\Modules\Task\Enums\COMMENTTYPE;
use App\Modules\Task\Services\TaskCommentService;
use Dcat\Admin\Form;
use Dcat\Admin\Grid;
use Dcat\Admin\Show;
use Dcat\Admin\Http\Controllers\AdminController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

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
            $grid->column('type', '类型')->display(function ($type) {
                if ($type instanceof TASKTYPE) {
                    return $type->label();
                }
                return TASKTYPE::tryFrom($type)?->label() ?? $type;
            })->label([
                'main' => 'primary',
                'sub' => 'info',
                'milestone' => 'warning',
                'bug' => 'danger',
                'feature' => 'success',
                'improvement' => 'default',
            ]);
            $grid->column('status', '状态')->display(function ($status) {
                if ($status instanceof TASKSTATUS) {
                    return $status->label();
                }
                return TASKSTATUS::tryFrom($status)?->label() ?? $status;
            })->label([
                'pending' => 'default',
                'in_progress' => 'info',
                'completed' => 'success',
                'blocked' => 'danger',
                'cancelled' => 'secondary',
                'on_hold' => 'warning',
            ]);
            $grid->column('priority', '优先级')->display(function ($priority) {
                if ($priority instanceof TASKPRIORITY) {
                    return $priority->label();
                }
                return TASKPRIORITY::tryFrom($priority)?->label() ?? $priority;
            })->label([
                'low' => 'default',
                'medium' => 'info',
                'high' => 'warning',
                'urgent' => 'danger',
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
                $filter->equal('type', '类型')->select(TASKTYPE::selectOptions());
                $filter->equal('status', '状态')->select(TASKSTATUS::selectOptions());
                $filter->equal('priority', '优先级')->select(TASKPRIORITY::selectOptions());
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
        return Show::make($id, Task::with(['user', 'agent', 'project', 'parentTask', 'subTasks', 'comments.user', 'comments.agent']), function (Show $show) {
            $show->field('id');
            $show->field('title', '任务标题');
            $show->field('description', '任务描述');
            $show->field('type', '类型')->as(function ($type) {
                if ($type instanceof TASKTYPE) {
                    return $type->label();
                }
                return TASKTYPE::tryFrom($type)?->label() ?? $type;
            });
            $show->field('status', '状态')->as(function ($status) {
                if ($status instanceof TASKSTATUS) {
                    return $status->label();
                }
                return TASKSTATUS::tryFrom($status)?->label() ?? $status;
            });
            $show->field('priority', '优先级')->as(function ($priority) {
                if ($priority instanceof TASKPRIORITY) {
                    return $priority->label();
                }
                return TASKPRIORITY::tryFrom($priority)?->label() ?? $priority;
            });
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

            // 添加评论部分
            $show->divider();
            $show->field('comments', '评论')->as(function ($comments) {
                if ($comments->isEmpty()) {
                    return '<div class="alert alert-info">暂无评论</div>';
                }

                $html = '<div class="comments-section">';
                foreach ($comments->sortBy('created_at') as $comment) {
                    $authorName = $comment->author_name;
                    $authorType = $comment->author_type;
                    $typeLabel = $comment->comment_type ? $comment->comment_type->label() : '一般';
                    $isInternal = $comment->is_internal ? '<span class="badge badge-warning">内部</span>' : '';
                    $isSystem = $comment->is_system ? '<span class="badge badge-info">系统</span>' : '';
                    $edited = $comment->isEdited() ? '<small class="text-muted">(已编辑)</small>' : '';

                    $html .= '<div class="card mb-3">';
                    $html .= '<div class="card-header">';
                    $html .= '<strong>' . htmlspecialchars($authorName) . '</strong>';
                    $html .= ' <span class="badge badge-secondary">' . $authorType . '</span>';
                    $html .= ' <span class="badge badge-primary">' . $typeLabel . '</span>';
                    $html .= ' ' . $isInternal . ' ' . $isSystem;
                    $html .= '<small class="text-muted float-right">' . $comment->created_at->format('Y-m-d H:i:s') . ' ' . $edited . '</small>';
                    $html .= '</div>';
                    $html .= '<div class="card-body">';
                    $html .= '<p>' . nl2br(htmlspecialchars($comment->content)) . '</p>';
                    if ($comment->attachments && !empty($comment->attachments)) {
                        $html .= '<div class="attachments"><strong>附件:</strong> ' . implode(', ', $comment->attachments) . '</div>';
                    }
                    $html .= '</div>';
                    $html .= '</div>';
                }
                $html .= '</div>';

                return $html;
            })->unescape();
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
            $form->select('type', '类型')->options(TASKTYPE::selectOptions())->default('main')->required();
            $form->select('status', '状态')->options(TASKSTATUS::selectOptions())->default('pending')->required();
            $form->select('priority', '优先级')->options(TASKPRIORITY::selectOptions())->default('medium')->required();
            $form->select('user_id', '用户')->options(User::pluck('name', 'id'))->required();
            $form->select('agent_id', 'Agent')->options(Agent::pluck('name', 'id'));
            $form->select('project_id', '项目')->options(Project::pluck('name', 'id'));
            $form->select('parent_task_id', '父任务')->options(Task::mainTasks()->pluck('title', 'id'));
            $form->number('progress', '进度')->min(0)->max(100)->default(0);
            $form->datetime('due_date', '截止时间');
            $form->text('estimated_hours', '预估工时')->placeholder('预估工时（小时）');
            $form->text('actual_hours', '实际工时')->placeholder('实际工时（小时）');
            $form->tags('tags', '标签');
            $form->textarea('metadata', '元数据')->placeholder('JSON格式的元数据');
            $form->textarea('result', '结果')->placeholder('JSON格式的任务结果');

            $form->display('created_at', '创建时间');
            $form->display('updated_at', '更新时间');
        });
    }

    /**
     * 添加评论
     */
    public function addComment(Request $request, $taskId)
    {
        $task = Task::findOrFail($taskId);

        $request->validate([
            'content' => 'required|string|max:5000',
            'comment_type' => 'nullable|string|in:general,progress,issue,solution,question,note',
            'is_internal' => 'nullable|boolean',
        ]);

        $commentService = app(TaskCommentService::class);

        try {
            $comment = $commentService->create(
                $task,
                $request->only(['content', 'comment_type', 'is_internal']),
                Auth::user()
            );

            return response()->json([
                'status' => 'success',
                'message' => '评论添加成功',
                'comment' => [
                    'id' => $comment->id,
                    'content' => $comment->content,
                    'author_name' => $comment->author_name,
                    'author_type' => $comment->author_type,
                    'comment_type' => $comment->comment_type?->label(),
                    'is_internal' => $comment->is_internal,
                    'created_at' => $comment->created_at->format('Y-m-d H:i:s'),
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => '评论添加失败: ' . $e->getMessage()
            ], 400);
        }
    }

    /**
     * 编辑评论
     */
    public function editComment(Request $request, $taskId, $commentId)
    {
        $task = Task::findOrFail($taskId);
        $comment = TaskComment::findOrFail($commentId);

        if ($comment->task_id !== $task->id) {
            return response()->json([
                'status' => 'error',
                'message' => '评论不属于此任务'
            ], 400);
        }

        $request->validate([
            'content' => 'required|string|max:5000',
        ]);

        $commentService = app(TaskCommentService::class);

        try {
            $updatedComment = $commentService->update(
                $comment,
                $request->only(['content']),
                Auth::user()
            );

            return response()->json([
                'status' => 'success',
                'message' => '评论更新成功',
                'comment' => [
                    'id' => $updatedComment->id,
                    'content' => $updatedComment->content,
                    'edited_at' => $updatedComment->edited_at?->format('Y-m-d H:i:s'),
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => '评论更新失败: ' . $e->getMessage()
            ], 400);
        }
    }

    /**
     * 删除评论
     */
    public function deleteComment($taskId, $commentId)
    {
        $task = Task::findOrFail($taskId);
        $comment = TaskComment::findOrFail($commentId);

        if ($comment->task_id !== $task->id) {
            return response()->json([
                'status' => 'error',
                'message' => '评论不属于此任务'
            ], 400);
        }

        $commentService = app(TaskCommentService::class);

        try {
            $commentService->delete($comment, Auth::user());

            return response()->json([
                'status' => 'success',
                'message' => '评论删除成功'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => '评论删除失败: ' . $e->getMessage()
            ], 400);
        }
    }
}
