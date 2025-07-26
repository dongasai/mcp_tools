<?php

namespace Modules\MCP\Tools;

use PhpMCP\Server\Attributes\MCPTool;
use Modules\Task\Services\TaskService;
use Modules\Task\Services\TaskCommentService;
use Modules\Task\Models\Task;
use Modules\Task\Enums\TASKSTATUS;
use Modules\Task\Enums\TASKTYPE;
use App\Modules\Agent\Services\AuthenticationService;
use App\Modules\Agent\Services\AuthorizationService;
use Modules\MCP\Services\ErrorHandlerService;

class TaskTool
{
    public function __construct(
        private TaskService $taskService,
        private TaskCommentService $commentService,
        private AuthenticationService $authService,
        private AuthorizationService $authzService,
        private ErrorHandlerService $errorHandler
    ) {}

    /**
     * 创建主任务
     */
    #[MCPTool(name: 'task_create_main')]
    public function createMainTask(string $title, string $description = '', string $priority = 'medium'): array
    {
        try {
            // 获取认证的Agent和用户
            $agent = $this->getCurrentAgent();
            $user = \Modules\User\Models\User::find($agent->user_id);

            // 检查Agent是否绑定了项目
            if (!$agent->project_id) {
                throw new \Exception('Agent is not bound to any project');
            }

            // 验证创建任务权限
            if (!$this->authzService->canPerformAction($agent, 'create_task')) {
                throw new \Exception('Permission denied: create_task');
            }

            $taskData = [
                'project_id' => $agent->project_id,
                'title' => $title,
                'description' => $description,
                'type' => TASKTYPE::MAIN->value,
                'status' => TASKSTATUS::PENDING->value,
                'priority' => $priority,
                'created_by' => $user->id,
                'agent_id' => $agent->id, // 关联创建的Agent
            ];

            $task = $this->taskService->create($user, $taskData);

            return [
                'success' => true,
                'message' => 'Main task created successfully',
                'data' => [
                    'task_id' => $task->id,
                    'title' => $task->title,
                    'status' => $task->status,
                    'type' => $task->type,
                    'created_at' => $task->created_at->toISOString()
                ]
            ];
        } catch (\Exception $e) {
            // 获取会话ID（如果有）
            $sessionId = request()->attributes->get('mcp_session_id');

            // 使用错误处理服务处理错误
            $errorResponse = $this->errorHandler->handleError($e, $sessionId, [
                'tool' => 'create_main_task',
                'project_id' => $agent->project_id ?? null,
                'agent_id' => $agent->identifier ?? 'unknown'
            ]);

            return $errorResponse->getData(true);
        }
    }

    /**
     * 创建子任务
     */
    #[MCPTool(name: 'task_create_sub')]
    public function createSubTask(string $parentTaskId, string $title, string $description = '', string $priority = 'medium'): array
    {
        try {
            $user = \Modules\User\Models\User::find($this->getUserIdFromAgent());
            
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
                    'status' => $task->status,
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
    #[MCPTool(name: 'task_list')]
    public function listTasks(string $status = '', string $type = '', bool $assignedToMe = false, int $limit = 20): array
    {
        try {
            $agent = $this->getCurrentAgent();
            $user = \Modules\User\Models\User::find($agent->user_id);

            // 检查Agent是否绑定了项目
            if (!$agent->project_id) {
                throw new \Exception('Agent is not bound to any project');
            }

            $filters = ['project_id' => $agent->project_id];
            if ($status) $filters['status'] = $status;
            if ($type) $filters['type'] = $type;
            if ($assignedToMe) $filters['agent_id'] = $agent->id;
            
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
    #[MCPTool(name: 'task_get')]
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
    #[MCPTool(name: 'task_complete')]
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
                    'status' => $task->status,
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
    #[MCPTool(name: 'task_add_comment')]
    public function addComment(string $taskId, string $content, string $commentType = 'general', bool $isInternal = false): array
    {
        try {
            $task = Task::findOrFail($taskId);
            $user = \Modules\User\Models\User::find($this->getUserIdFromAgent());
            
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
    #[MCPTool(name: 'task_get_assigned')]
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
    private function getCurrentAgent(): \App\Modules\Agent\Models\Agent
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
