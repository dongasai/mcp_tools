<?php

namespace Modules\MCP\Resources;

use PhpMCP\Server\Attributes\MCPResource;
use Modules\Task\Services\TaskService;
use Modules\Agent\Services\AuthenticationService;
use Modules\Agent\Services\AuthorizationService;

class TaskResource
{
    public function __construct(
        private TaskService $taskService,
        private AuthenticationService $authService,
        private AuthorizationService $authzService
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
        $user = \Modules\User\Models\User::find($this->getUserIdFromAgent());
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
        $task = \Modules\Task\Models\Task::findOrFail($taskId);

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
        $tasks = \Modules\Task\Models\Task::where('assigned_to', $agentId)->get();
        
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
        $user = \Modules\User\Models\User::find($this->getUserIdFromAgent());
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
        // 从请求中获取认证信息
        $authInfo = $this->authService->extractAuthFromRequest(request());

        if (!$authInfo['token']) {
            throw new \Exception('No authentication token provided');
        }

        // 认证Agent
        $agent = $this->authService->authenticate($authInfo['token'], $authInfo['agent_id']);

        if (!$agent) {
            throw new \Exception('Invalid authentication token or agent ID');
        }

        return $agent->user_id;
    }

    /**
     * 获取当前认证的Agent
     */
    private function getCurrentAgent(): \Modules\Agent\Models\Agent
    {
        $authInfo = $this->authService->extractAuthFromRequest(request());

        if (!$authInfo['token']) {
            throw new \Exception('No authentication token provided');
        }

        $agent = $this->authService->authenticate($authInfo['token'], $authInfo['agent_id']);

        if (!$agent) {
            throw new \Exception('Invalid authentication token or agent ID');
        }

        return $agent;
    }
}
