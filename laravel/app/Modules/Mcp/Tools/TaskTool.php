<?php

namespace App\Modules\Mcp\Tools;

use PhpMcp\Server\Attributes\McpTool;
use App\Modules\Task\Services\TaskService;
use App\Modules\Task\Services\TaskCommentService;
use App\Modules\Task\Models\Task;
use App\Modules\Task\Enums\TASKSTATUS;
use App\Modules\Task\Enums\TASKTYPE;

class TaskTool
{
    public function __construct(
        private TaskService $taskService,
        private TaskCommentService $commentService
    ) {}

    /**
     * 创建主任务
     */
    #[McpTool(name: 'create_main_task')]
    public function createMainTask(string $projectId, string $title, string $description = '', string $priority = 'medium'): array
    {
        try {
            $user = \App\Modules\User\Models\User::find($this->getUserIdFromAgent());
            
            $taskData = [
                'project_id' => $projectId,
                'title' => $title,
                'description' => $description,
                'type' => TASKTYPE::MAIN->value,
                'status' => TASKSTATUS::PENDING->value,
                'priority' => $priority,
                'created_by' => $user->id,
            ];

            $task = $this->taskService->create($user, $taskData);

            return [
                'success' => true,
                'message' => 'Main task created successfully',
                'data' => [
                    'task_id' => $task->id,
                    'title' => $task->title,
                    'status' => $task->status->value,
                    'type' => $task->type->value,
                    'created_at' => $task->created_at->toISOString()
                ]
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * 创建子任务
     */
    #[McpTool(name: 'create_sub_task')]
    public function createSubTask(string $parentTaskId, string $title, string $description = '', string $priority = 'medium'): array
    {
        try {
            $user = \App\Modules\User\Models\User::find($this->getUserIdFromAgent());
            
            $taskData = [
                'parent_task_id' => $parentTaskId,
                'title' => $title,
                'description' => $description,
                'type' => TASKTYPE::SUB->value,
                'status' => TASKSTATUS::PENDING->value,
                'priority' => $priority,
                'assigned_to' => (string)$user->id, // 暂时分配给当前用户
                'created_by' => $user->id,
            ];

            $task = $this->taskService->create($user, $taskData);

            return [
                'success' => true,
                'message' => 'Sub task created successfully',
                'data' => [
                    'task_id' => $task->id,
                    'title' => $task->title,
                    'parent_task_id' => $task->parent_task_id,
                    'status' => $task->status->value,
                    'assigned_to' => $task->assigned_to,
                    'created_at' => $task->created_at->toISOString()
                ]
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * 获取任务列表
     */
    #[McpTool(name: 'list_tasks')]
    public function listTasks(string $status = '', string $type = '', string $projectId = ''): array
    {
        try {
            $user = \App\Modules\User\Models\User::find($this->getUserIdFromAgent());
            
            $filters = [];
            if ($status) $filters['status'] = $status;
            if ($type) $filters['type'] = $type;
            if ($projectId) $filters['project_id'] = $projectId;
            
            $tasks = $this->taskService->getUserTasks($user, $filters);

            return [
                'success' => true,
                'data' => $tasks->map(function ($task) {
                    return [
                        'id' => $task->id,
                        'title' => $task->title,
                        'type' => $task->type->value,
                        'status' => $task->status->value,
                        'priority' => $task->priority->value,
                        'progress' => $task->progress,
                        'due_date' => $task->due_date?->toISOString(),
                        'project_id' => $task->project_id,
                        'assigned_to' => $task->assigned_to,
                        'created_at' => $task->created_at->toISOString(),
                    ];
                })->toArray()
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * 获取任务详情
     */
    #[McpTool(name: 'get_task')]
    public function getTask(string $taskId): array
    {
        try {
            $task = Task::findOrFail($taskId);

            return [
                'success' => true,
                'data' => [
                    'id' => $task->id,
                    'title' => $task->title,
                    'description' => $task->description,
                    'type' => $task->type->value,
                    'status' => $task->status->value,
                    'priority' => $task->priority->value,
                    'progress' => $task->progress,
                    'due_date' => $task->due_date?->toISOString(),
                    'estimated_hours' => $task->estimated_hours,
                    'actual_hours' => $task->actual_hours,
                    'tags' => $task->tags,
                    'metadata' => $task->metadata,
                    'result' => $task->result,
                    'parent_task_id' => $task->parent_task_id,
                    'project_id' => $task->project_id,
                    'assigned_to' => $task->assigned_to,
                    'created_by' => $task->created_by,
                    'created_at' => $task->created_at->toISOString(),
                    'updated_at' => $task->updated_at->toISOString(),
                ]
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * 完成任务
     */
    #[McpTool(name: 'complete_task')]
    public function completeTask(string $taskId, string $result = ''): array
    {
        try {
            $task = Task::findOrFail($taskId);
            
            $updateData = [
                'status' => TASKSTATUS::COMPLETED->value,
                'progress' => 100
            ];
            
            if ($result) {
                $updateData['result'] = $result;
            }

            $task = $this->taskService->update($task, $updateData);

            return [
                'success' => true,
                'message' => 'Task completed successfully',
                'data' => [
                    'task_id' => $task->id,
                    'title' => $task->title,
                    'status' => $task->status->value,
                    'progress' => $task->progress,
                    'completed_at' => $task->updated_at->toISOString()
                ]
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * 添加评论
     */
    #[McpTool(name: 'add_comment')]
    public function addComment(string $taskId, string $content, string $commentType = 'general', bool $isInternal = false): array
    {
        try {
            $task = Task::findOrFail($taskId);
            $user = \App\Modules\User\Models\User::find($this->getUserIdFromAgent());
            
            $commentData = [
                'content' => $content,
                'comment_type' => $commentType,
                'is_internal' => $isInternal,
            ];

            $comment = $this->commentService->create($task, $commentData, $user);

            return [
                'success' => true,
                'message' => 'Comment added successfully',
                'data' => [
                    'comment_id' => $comment->id,
                    'task_id' => $comment->task_id,
                    'content' => $comment->content,
                    'comment_type' => $comment->comment_type,
                    'created_at' => $comment->created_at->toISOString()
                ]
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * 获取分配给当前Agent的任务
     */
    #[McpTool(name: 'get_assigned_tasks')]
    public function getAssignedTasks(): array
    {
        try {
            $agentId = $this->getAgentId();
            $tasks = Task::where('assigned_to', $agentId)->get();

            return [
                'success' => true,
                'data' => $tasks->map(function ($task) {
                    return [
                        'id' => $task->id,
                        'title' => $task->title,
                        'type' => $task->type->value,
                        'status' => $task->status->value,
                        'priority' => $task->priority->value,
                        'progress' => $task->progress,
                        'due_date' => $task->due_date?->toISOString(),
                        'parent_task_id' => $task->parent_task_id,
                        'created_at' => $task->created_at->toISOString(),
                    ];
                })->toArray()
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * 获取当前Agent ID
     */
    private function getAgentId(): string
    {
        return request()->header('X-Agent-ID', 'test-agent-001');
    }

    /**
     * 从Agent获取用户ID
     */
    private function getUserIdFromAgent(): int
    {
        // 这里应该通过Agent服务获取关联的用户ID
        // 暂时返回默认值
        return 1;
    }
}
