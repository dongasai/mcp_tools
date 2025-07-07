<?php

namespace App\Modules\Mcp\Resources;

use PhpMcp\Server\Resources\Resource;
use App\Modules\Task\Services\TaskService;
use App\Modules\Mcp\Services\McpService;

class TaskResource extends Resource
{
    public function __construct(
        private TaskService $taskService,
        private McpService $mcpService
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
        
        // 验证Agent权限
        $agentId = $this->getAgentId();
        if (!$this->mcpService->validateAgentAccess($agentId, 'task', 'read')) {
            throw new \Exception('Access denied');
        }

        // 记录访问
        $this->mcpService->logSession($agentId, 'task_read', ['uri' => $uri]);

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
        $tasks = $this->taskService->getAllTasks();
        
        return [
            'type' => 'task_list',
            'data' => $tasks->map(function ($task) {
                return [
                    'id' => $task->id,
                    'title' => $task->title,
                    'description' => $task->description,
                    'type' => $task->type,
                    'status' => $task->status,
                    'priority' => $task->priority,
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
        $task = $this->taskService->findTask($taskId);
        
        if (!$task) {
            throw new \Exception('Task not found');
        }

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
        $tasks = $this->taskService->getTasksByAgent($agentId);
        
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
        $tasks = $this->taskService->getTasksByStatus($status);
        
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
}
