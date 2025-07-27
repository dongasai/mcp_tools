<?php

namespace Modules\Task\Services;

use Modules\Task\Models\Task;
use Modules\Task\Models\TaskComment;
use Modules\User\Models\User;
use App\Modules\Agent\Models\Agent;
use App\Modules\Core\Contracts\LogInterface;
use App\Modules\Core\Contracts\EventInterface;
use App\Modules\Core\Validators\SimpleValidator;
use Modules\Task\Enums\COMMENTTYPE;
use Modules\Task\Events\TaskCommentCreated;
use Modules\Task\Events\TaskCommentUpdated;
use Modules\Task\Events\TaskCommentDeleted;
use Illuminate\Support\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class TaskCommentService
{
    protected LogInterface $logger;
    protected EventInterface $eventDispatcher;

    public function __construct(
        LogInterface $logger,
        EventInterface $eventDispatcher
    ) {
        $this->logger = $logger;
        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * 创建评论
     */
    public function create(Task $task, array $data, ?User $user = null, ?Agent $agent = null): TaskComment
    {
        // 验证数据
        $validatedData = $this->validateCommentData($data);

        // 权限检查
        $this->checkCreatePermission($task, $user, $agent);

        // 创建评论
        $comment = new TaskComment();
        $comment->task_id = $task->id;
        $comment->user_id = $user?->id;
        $comment->agent_id = $agent?->id;
        $comment->content = $validatedData['content'];
        $comment->comment_type = COMMENTTYPE::fromString($validatedData['comment_type'] ?? 'general') ?? COMMENTTYPE::GENERAL;
        $comment->is_internal = $validatedData['is_internal'] ?? false;
        $comment->is_system = $validatedData['is_system'] ?? false;
        $comment->metadata = $validatedData['metadata'] ?? null;
        $comment->attachments = $validatedData['attachments'] ?? null;
        $comment->parent_comment_id = $validatedData['parent_comment_id'] ?? null;

        $comment->save();

        // 记录日志
        $this->logger->info('Task comment created', [
            'task_id' => $task->id,
            'comment_id' => $comment->id,
            'user_id' => $user?->id,
            'agent_id' => $agent?->id,
            'comment_type' => $comment->comment_type->value,
        ]);

        // 分发事件
        $this->eventDispatcher->dispatch(new TaskCommentCreated($comment));

        return $comment;
    }

    /**
     * 更新评论
     */
    public function update(TaskComment $comment, array $data, User $user): TaskComment
    {
        // 权限检查
        if (!$comment->canEdit($user)) {
            throw new \Exception('您没有权限编辑此评论');
        }

        // 验证数据
        $validatedData = $this->validateCommentData($data, false);

        // 更新评论
        if (isset($validatedData['content'])) {
            $comment->content = $validatedData['content'];
        }
        
        if (isset($validatedData['comment_type'])) {
            $comment->comment_type = COMMENTTYPE::fromString($validatedData['comment_type']) ?? $comment->comment_type;
        }
        
        if (isset($validatedData['is_internal'])) {
            $comment->is_internal = $validatedData['is_internal'];
        }
        
        if (isset($validatedData['metadata'])) {
            $comment->metadata = $validatedData['metadata'];
        }
        
        if (isset($validatedData['attachments'])) {
            $comment->attachments = $validatedData['attachments'];
        }

        $comment->edited_at = now();
        $comment->save();

        // 记录日志
        $this->logger->info('Task comment updated', [
            'task_id' => $comment->task_id,
            'comment_id' => $comment->id,
            'user_id' => $user->id,
        ]);

        // 分发事件
        $this->eventDispatcher->dispatch(new TaskCommentUpdated($comment));

        return $comment;
    }

    /**
     * 删除评论
     */
    public function delete(TaskComment $comment, User $user): bool
    {
        // 权限检查
        if (!$comment->canDelete($user)) {
            throw new \Exception('您没有权限删除此评论');
        }

        $commentId = $comment->id;
        $taskId = $comment->task_id;

        // 软删除评论
        $comment->delete();

        // 记录日志
        $this->logger->info('Task comment deleted', [
            'task_id' => $taskId,
            'comment_id' => $commentId,
            'user_id' => $user->id,
        ]);

        // 分发事件
        $this->eventDispatcher->dispatch(new TaskCommentDeleted($comment));

        return true;
    }

    /**
     * 获取任务评论列表
     */
    public function getTaskComments(
        Task $task, 
        array $filters = [], 
        int $page = 1, 
        int $perPage = 20
    ): LengthAwarePaginator {
        $query = TaskComment::where('task_id', $task->id)
            ->with(['user', 'agent', 'replies.user', 'replies.agent']);

        // 应用过滤器
        if (isset($filters['comment_type'])) {
            $query->ofType($filters['comment_type']);
        }

        if (isset($filters['is_internal'])) {
            if ($filters['is_internal']) {
                $query->internal();
            } else {
                $query->public();
            }
        }

        if (isset($filters['author_type'])) {
            switch ($filters['author_type']) {
                case 'user':
                    $query->byUser();
                    break;
                case 'agent':
                    $query->byAgent();
                    break;
                case 'system':
                    $query->system();
                    break;
            }
        }

        if (isset($filters['include_replies']) && !$filters['include_replies']) {
            $query->topLevel();
        }

        // 排序
        $sortOrder = $filters['sort_order'] ?? 'asc';
        if ($sortOrder === 'desc') {
            $query->latest();
        } else {
            $query->oldest();
        }

        return $query->paginate($perPage, ['*'], 'page', $page);
    }

    /**
     * 回复评论
     */
    public function reply(TaskComment $parentComment, array $data, ?User $user = null, ?Agent $agent = null): TaskComment
    {
        $data['parent_comment_id'] = $parentComment->id;
        return $this->create($parentComment->task, $data, $user, $agent);
    }

    /**
     * 创建系统评论
     */
    public function createSystemComment(Task $task, string $content, array $metadata = []): TaskComment
    {
        return $this->create($task, [
            'content' => $content,
            'comment_type' => COMMENTTYPE::SYSTEM->value,
            'is_system' => true,
            'is_internal' => true,
            'metadata' => $metadata,
        ]);
    }

    /**
     * 验证评论数据
     */
    protected function validateCommentData(array $data, bool $isCreate = true): array
    {
        $rules = [
            'content' => $isCreate ? 'required|string|min:1|max:5000' : 'string|min:1|max:5000',
            'comment_type' => 'string|in:general,status_update,progress_report,issue_report,solution,question,answer,system',
            'is_internal' => 'boolean',
            'is_system' => 'boolean',
            'metadata' => 'array',
            'attachments' => 'array',
            'parent_comment_id' => 'integer|exists:task_comments,id',
        ];

        $validator = SimpleValidator::make($data, $rules);

        if ($validator->hasErrors()) {
            throw new \Exception('评论数据验证失败: ' . $validator->getFirstError());
        }

        return $validator->validate($data, $rules);
    }

    /**
     * 检查创建权限
     */
    protected function checkCreatePermission(Task $task, ?User $user, ?Agent $agent): void
    {
        // 系统评论不需要权限检查
        if (!$user && !$agent) {
            return;
        }

        // 用户权限检查
        if ($user) {
            // 任务创建者、分配者或项目成员可以评论
            if ($task->user_id === $user->id || 
                $task->assigned_to === $user->name ||
                $this->isProjectMember($task, $user)) {
                return;
            }
        }

        // Agent权限检查
        if ($agent) {
            // 任务关联的Agent可以评论
            if ($task->agent_id === $agent->id) {
                return;
            }
        }

        throw new \Exception('您没有权限在此任务上添加评论');
    }

    /**
     * 检查是否为项目成员
     */
    protected function isProjectMember(Task $task, User $user): bool
    {
        if (!$task->project) {
            return false;
        }

        // 检查项目成员关系
        return $task->project->members()->where('user_id', $user->id)->exists();
    }
}
