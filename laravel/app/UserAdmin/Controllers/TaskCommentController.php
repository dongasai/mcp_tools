<?php

namespace App\UserAdmin\Controllers;

use App\Modules\Task\Models\Task;
use App\Modules\Task\Models\TaskComment;
use App\Modules\Task\Enums\COMMENTTYPE;
use App\Modules\Task\Services\TaskCommentService;
use Dcat\Admin\Http\Controllers\AdminController;
use Dcat\Admin\Form;
use Dcat\Admin\Grid;
use Dcat\Admin\Show;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;

class TaskCommentController extends AdminController
{
    protected $commentService;

    public function __construct(TaskCommentService $commentService)
    {
        $this->commentService = $commentService;
    }

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        return Grid::make(TaskComment::with(['task', 'user']), function (Grid $grid) {
            $grid->column('id')->sortable();
            $grid->column('task.title', '任务标题');
            $grid->column('user.name', '评论者');
            $grid->column('content', '评论内容')->limit(50);
            $grid->column('comment_type', '类型')->display(function ($value) {
                $typeLabels = [
                    'general' => '一般',
                    'progress_report' => '进度',
                    'issue_report' => '问题',
                    'solution' => '解决方案',
                    'question' => '疑问',
                    'system' => '备注'
                ];
                // 确保$value是字符串
                if (is_string($value)) {
                    $valueStr = $value;
                } elseif ($value instanceof \App\Modules\Task\Enums\COMMENTTYPE) {
                    $valueStr = $value->value;
                } else {
                    $valueStr = (string)$value;
                }
                $label = $typeLabels[$valueStr] ?? $valueStr;
                return '<span class="label label-primary">' . $label . '</span>';
            });
            $grid->column('is_internal', '内部评论')->bool();
            $grid->column('created_at', '创建时间');
            
            $grid->filter(function($filter) {
                $filter->like('content', '评论内容');
                $filter->equal('task_id', '任务')->select(
                    Task::where('user_id', auth('user-admin')->id())->pluck('title', 'id')
                );

                // 类型筛选
                $typeOptions = [
                    'general' => '一般',
                    'progress_report' => '进度',
                    'issue_report' => '问题',
                    'solution' => '解决方案',
                    'question' => '疑问',
                    'system' => '备注'
                ];
                $filter->equal('comment_type', '类型')->select($typeOptions);
                
                $filter->equal('is_internal', '内部评论')->select([
                    1 => '是',
                    0 => '否'
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
        return Show::make($id, TaskComment::with(['task', 'user']), function (Show $show) {
            $show->field('id');
            $show->field('task.title', '任务标题');
            $show->field('user.name', '评论者');
            $show->field('content', '评论内容');
            $show->field('type', '类型')->as(function ($value) {
                if ($value instanceof COMMENTTYPE) {
                    return $value->label();
                }
                return $value;
            });
            $show->field('is_internal', '内部评论')->using([1 => '是', 0 => '否']);
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
        return Form::make(TaskComment::with(['task']), function (Form $form) {
            $form->display('id');

            $taskId = request()->get('task_id');
            $isEditing = request()->route('task_comment'); // 检查是否是编辑模式

            if ($taskId && !$isEditing) {
                // 创建模式：通过task_id参数指定任务
                $task = Task::findOrFail($taskId);
                $form->display('task_title', '任务标题')->value($task->title);
                $form->hidden('task_id')->value($task->id);
            } elseif ($isEditing) {
                // 编辑模式：显示当前任务标题，不允许修改
                $form->display('task.title', '任务标题');
                $form->hidden('task_id');
            } else {
                // 创建模式：没有指定任务，显示选择框
                $form->select('task_id', '选择任务')->options(
                    Task::where('user_id', auth('user-admin')->id())->pluck('title', 'id')
                )->required();
            }
            
            $form->textarea('content', '评论内容')->required();
            
            $form->select('comment_type', '评论类型')->options([
                'general' => '一般',
                'progress_report' => '进度',
                'issue_report' => '问题',
                'solution' => '解决方案',
                'question' => '疑问',
                'system' => '备注'
            ])->default('general')->required();
            
            $form->switch('is_internal', '内部评论')->help('仅团队成员可见');
            
            $form->hidden('user_id')->value(1); // 临时硬编码用户ID
            
            $form->display('created_at', '创建时间');
            $form->display('updated_at', '更新时间');

            $form->saving(function (Form $form) {
                $form->user_id = 1; // 强制设置正确的用户ID
            });
            

        });
    }

    /**
     * 删除评论
     */
    public function destroy($id)
    {
        Log::info('TaskCommentController destroy called with id: ' . $id);

        $comment = TaskComment::findOrFail($id);
        Log::info('Found comment: ' . $comment->id . ', user_id: ' . $comment->user_id);

        // 验证权限：只有评论作者可以删除自己的评论
        if ($comment->user_id != 1) { // 临时硬编码用户ID
            Log::info('Permission denied for user_id: ' . $comment->user_id);
            return response()->json(['status' => false, 'message' => '您没有权限删除此评论']);
        }

        $comment->delete();
        Log::info('Comment deleted successfully');

        // 重定向回任务详情页面
        return redirect()->route('tasks.show', $comment->task_id)->with('success', '评论删除成功');
    }
}
