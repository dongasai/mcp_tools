<?php

namespace App\Modules\Task\Events;

use App\Modules\Task\Models\TaskComment;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TaskCommentCreated
{
    use Dispatchable, SerializesModels;

    public TaskComment $comment;

    /**
     * Create a new event instance.
     */
    public function __construct(TaskComment $comment)
    {
        $this->comment = $comment;
    }

    /**
     * 获取事件名称
     */
    public function getName(): string
    {
        return 'task.comment.created';
    }

    /**
     * 获取事件数据
     */
    public function getData(): array
    {
        return [
            'comment_id' => $this->comment->id,
            'task_id' => $this->comment->task_id,
            'user_id' => $this->comment->user_id,
            'agent_id' => $this->comment->agent_id,
            'comment_type' => $this->comment->comment_type->value,
            'content' => $this->comment->content,
            'is_internal' => $this->comment->is_internal,
            'is_system' => $this->comment->is_system,
            'parent_comment_id' => $this->comment->parent_comment_id,
            'created_at' => $this->comment->created_at->toISOString(),
        ];
    }

    /**
     * 获取任务信息
     */
    public function getTask()
    {
        return $this->comment->task;
    }

    /**
     * 获取评论作者
     */
    public function getAuthor()
    {
        if ($this->comment->user_id) {
            return $this->comment->user;
        }
        
        if ($this->comment->agent_id) {
            return $this->comment->agent;
        }
        
        return null;
    }

    /**
     * 检查是否需要通知
     */
    public function shouldNotify(): bool
    {
        return $this->comment->comment_type->shouldNotify();
    }

    /**
     * 获取通知接收者
     */
    public function getNotificationRecipients(): array
    {
        $recipients = [];
        $task = $this->getTask();
        
        // 任务创建者
        if ($task->user && $task->user->id !== $this->comment->user_id) {
            $recipients[] = $task->user;
        }
        
        // 任务分配者（如果不同于创建者）
        if ($task->assigned_to && $task->user && $task->user->name !== $task->assigned_to) {
            // 这里需要根据assigned_to字段查找用户
            // 暂时跳过，因为assigned_to是字符串字段
        }
        
        // 项目成员（如果是项目任务）
        if ($task->project) {
            $projectMembers = $task->project->members()
                ->where('user_id', '!=', $this->comment->user_id)
                ->get()
                ->pluck('user');
            
            $recipients = array_merge($recipients, $projectMembers->toArray());
        }
        
        return array_unique($recipients, SORT_REGULAR);
    }
}
