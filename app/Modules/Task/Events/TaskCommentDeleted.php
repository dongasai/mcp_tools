<?php

namespace App\Modules\Task\Events;

use App\Modules\Task\Models\TaskComment;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TaskCommentDeleted
{
    use Dispatchable, SerializesModels;

    public TaskComment $comment;
    public array $commentData;

    /**
     * Create a new event instance.
     */
    public function __construct(TaskComment $comment)
    {
        $this->comment = $comment;
        
        // 保存评论数据，因为删除后可能无法访问
        $this->commentData = [
            'id' => $comment->id,
            'task_id' => $comment->task_id,
            'user_id' => $comment->user_id,
            'agent_id' => $comment->agent_id,
            'content' => $comment->content,
            'comment_type' => $comment->comment_type->value,
            'is_internal' => $comment->is_internal,
            'is_system' => $comment->is_system,
            'parent_comment_id' => $comment->parent_comment_id,
            'created_at' => $comment->created_at->toISOString(),
            'deleted_at' => now()->toISOString(),
        ];
    }

    /**
     * 获取事件名称
     */
    public function getName(): string
    {
        return 'task.comment.deleted';
    }

    /**
     * 获取事件数据
     */
    public function getData(): array
    {
        return $this->commentData;
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
        if ($this->commentData['user_id']) {
            return $this->comment->user;
        }
        
        if ($this->commentData['agent_id']) {
            return $this->comment->agent;
        }
        
        return null;
    }

    /**
     * 获取评论ID
     */
    public function getCommentId(): int
    {
        return $this->commentData['id'];
    }

    /**
     * 获取任务ID
     */
    public function getTaskId(): int
    {
        return $this->commentData['task_id'];
    }

    /**
     * 检查是否为回复评论
     */
    public function isReply(): bool
    {
        return !is_null($this->commentData['parent_comment_id']);
    }

    /**
     * 检查是否需要通知
     */
    public function shouldNotify(): bool
    {
        // 系统评论删除不需要通知
        return !$this->commentData['is_system'];
    }

    /**
     * 获取通知接收者
     */
    public function getNotificationRecipients(): array
    {
        $recipients = [];
        $task = $this->getTask();
        
        if (!$task) {
            return $recipients;
        }
        
        // 任务创建者
        if ($task->user && $task->user->id !== $this->commentData['user_id']) {
            $recipients[] = $task->user;
        }
        
        // 项目成员（如果是项目任务）
        if ($task->project) {
            $projectMembers = $task->project->members()
                ->where('user_id', '!=', $this->commentData['user_id'])
                ->get()
                ->pluck('user');
            
            $recipients = array_merge($recipients, $projectMembers->toArray());
        }
        
        return array_unique($recipients, SORT_REGULAR);
    }
}
