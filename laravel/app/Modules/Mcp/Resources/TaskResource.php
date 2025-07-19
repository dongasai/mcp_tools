<?php

namespace App\Modules\Mcp\Resources;

use PhpMcp\Server\Attributes\McpResource;
use App\Modules\Task\Services\TaskService;

class TaskResource
{
    public function __construct(
        private TaskService $taskService
    ) {}

    /**
     * 获取资源URI模式
     */
    public function getUriTemplate(): string
    {
        return 'task://{path}';
    }

    /**
     * 获取资源名称
     */
    public function getName(): string
    {
        return 'task';
    }

    /**
     * 获取资源描述
     */
    public function getDescription(): string
    {
        return 'Access to task information and management';
    }

    /**
     * 处理资源请求
     */
    public function read(string $uri): array
    {
        // 解析URI
        $path = $this->parseUri($uri);

        // 根据路径返回不同的数据
        if (str_starts_with($path, 'assigned/')) {
            $agentId = str_replace('assigned/', '', $path);
            return $this->getAssignedTasks($agentId);
        }

        if (str_starts_with($path, 'status/')) {
            $status = str_replace('status/', '', $path);
            return $this->getTasksByStatus($status);
        }

        return match ($path) {
            'list' => $this->getTaskList(),
            default => $this->getTask($path)
        };
    }

    /**
     * 获取任务列表
     */
    private function getTaskList(): array
    {
        $user = \App\Modules\User\Models\User::find($this->getUserIdFromAgent());
        $tasks = $this->taskService->getUserTasks($user);

        return [
            'type' => 'task_list',
            'data' => $tasks->map(function ($task) {
                return [
                    'id' => $task->id,
                    'title' => $task->title,
                    'description' => $task->description,
                    'type' => $task->type->value,
                    'status' => $task->status->value,
                    'priority' => $task->priority->value,
                    'progress' => $task->progress,
                    'due_date' => $task->due_date?->toISOString(),
                    'created_at' => $task->created_at->toISOString(),
                    'updated_at' => $task->updated_at->toISOString(),
                ];
            })->toArray()
        ];
    }

    /**
     * 获取单个任务
     */
    private function getTask(string $taskId): array
    {
        $task = \App\Modules\Task\Models\Task::findOrFail($taskId);

        return [
            'type' => 'task_detail',
            'data' => [
                'id' => $task->id,
                'title' => $task->title,
                'description' => $task->description,
                'type' => $task->type,
                'status' => $task->status,
                'priority' => $task->priority,
                'progress' => $task->progress,
                'due_date' => $task->due_date?->toISOString(),
                'estimated_hours' => $task->estimated_hours,
                'actual_hours' => $task->actual_hours,
                'tags' => $task->tags,
                'metadata' => $task->metadata,
                'result' => $task->result,
                'user' => [
                    'id' => $task->user->id,
                    'name' => $task->user->name,
                ],
                'project' => $task->project ? [
                    'id' => $task->project->id,
                    'name' => $task->project->name,
                ] : null,
                'created_at' => $task->created_at->toISOString(),
                'updated_at' => $task->updated_at->toISOString(),
            ]
        ];
    }

    /**
     * 获取分配给特定Agent的任务
     */
    private function getAssignedTasks(string $agentId): array
    {
        $tasks = \App\Modules\Task\Models\Task::where('assigned_to', $agentId)->get();
        
        return [
            'type' => 'assigned_tasks',
            'agent_id' => $agentId,
            'data' => $tasks->map(function ($task) {
                return [
                    'id' => $task->id,
                    'title' => $task->title,
                    'status' => $task->status,
                    'priority' => $task->priority,
                    'progress' => $task->progress,
                    'due_date' => $task->due_date?->toISOString(),
                ];
            })->toArray()
        ];
    }

    /**
     * 按状态获取任务
     */
    private function getTasksByStatus(string $status): array
    {
        $user = \App\Modules\User\Models\User::find($this->getUserIdFromAgent());
        $tasks = $this->taskService->getUserTasks($user, ['status' => $status]);
        
        return [
            'type' => 'tasks_by_status',
            'status' => $status,
            'data' => $tasks->map(function ($task) {
                return [
                    'id' => $task->id,
                    'title' => $task->title,
                    'priority' => $task->priority,
                    'progress' => $task->progress,
                    'created_at' => $task->created_at->toISOString(),
                ];
            })->toArray()
        ];
    }

    /**
     * 解析URI路径
     */
    private function parseUri(string $uri): string
    {
        return str_replace('task://', '', $uri);
    }

    /**
     * 获取当前Agent ID
     */
    private function getAgentId(): string
    {
        return request()->header('X-Agent-ID', 'unknown');
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
