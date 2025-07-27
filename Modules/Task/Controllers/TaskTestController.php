<?php

namespace Modules\Task\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Task\Models\Task;
use Modules\Task\Services\TaskService;
use Modules\User\Models\User;
use Modules\Agent\Models\Agent;
use Modules\Project\Models\Project;

class TaskTestController extends Controller
{
    protected TaskService $taskService;

    public function __construct(TaskService $taskService)
    {
        $this->taskService = $taskService;
    }

    /**
     * 快速创建任务测试
     */
    public function quickCreate(Request $request): JsonResponse
    {
        try {
            $data = $request->all();
            
            // 简单验证
            if (empty($data['title']) || empty($data['user_id'])) {
                return response()->json([
                    'success' => false,
                    'error' => 'Title and user_id are required',
                ], 400);
            }

            // 查找用户
            $user = User::find($data['user_id']);
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'error' => 'User not found',
                ], 404);
            }

            // 创建任务
            $task = $this->taskService->create($user, [
                'title' => $data['title'],
                'description' => $data['description'] ?? 'Test task created via API',
                'type' => $data['type'] ?? 'main',
                'priority' => $data['priority'] ?? 'medium',
                'project_id' => $data['project_id'] ?? null,
                'agent_id' => $data['agent_id'] ?? null,
                'parent_task_id' => $data['parent_task_id'] ?? null,
                'assigned_to' => $data['assigned_to'] ?? null,
                'due_date' => $data['due_date'] ?? null,
                'estimated_hours' => $data['estimated_hours'] ?? null,
                'tags' => $data['tags'] ?? [],
            ]);

            return response()->json([
                'success' => true,
                'data' => $task,
                'message' => 'Task created successfully',
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to create task: ' . $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ], 500);
        }
    }

    /**
     * 获取所有任务
     */
    public function getTasks(): JsonResponse
    {
        try {
            $tasks = Task::with(['user', 'agent', 'project', 'parentTask'])->get();
            
            return response()->json([
                'success' => true,
                'data' => $tasks,
                'count' => $tasks->count(),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to get tasks: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * 获取任务统计信息
     */
    public function getStats(): JsonResponse
    {
        try {
            $stats = $this->taskService->getSystemStats();
            
            return response()->json([
                'success' => true,
                'data' => $stats,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to get stats: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * 获取用户的任务
     */
    public function getUserTasks(int $userId): JsonResponse
    {
        try {
            $user = User::find($userId);
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'error' => 'User not found',
                ], 404);
            }

            $tasks = $this->taskService->getUserTasks($user);
            
            return response()->json([
                'success' => true,
                'data' => $tasks,
                'count' => $tasks->count(),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to get user tasks: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * 获取项目的任务
     */
    public function getProjectTasks(int $projectId): JsonResponse
    {
        try {
            $project = Project::find($projectId);
            if (!$project) {
                return response()->json([
                    'success' => false,
                    'error' => 'Project not found',
                ], 404);
            }

            $tasks = Task::byProject($projectId)->with(['user', 'agent', 'parentTask'])->get();
            
            return response()->json([
                'success' => true,
                'data' => $tasks,
                'count' => $tasks->count(),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to get project tasks: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * 获取Agent的任务
     */
    public function getAgentTasks(int $agentId): JsonResponse
    {
        try {
            $agent = Agent::find($agentId);
            if (!$agent) {
                return response()->json([
                    'success' => false,
                    'error' => 'Agent not found',
                ], 404);
            }

            $tasks = Task::byAgent($agentId)->with(['user', 'project', 'parentTask'])->get();
            
            return response()->json([
                'success' => true,
                'data' => $tasks,
                'count' => $tasks->count(),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to get agent tasks: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * 测试任务开始
     */
    public function testStart(int $id): JsonResponse
    {
        try {
            $task = Task::find($id);
            
            if (!$task) {
                return response()->json([
                    'success' => false,
                    'error' => 'Task not found',
                ], 404);
            }

            $startedTask = $this->taskService->startTask($task);
            
            return response()->json([
                'success' => true,
                'data' => $startedTask,
                'message' => 'Task started successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to start task: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * 测试任务完成
     */
    public function testComplete(int $id, Request $request): JsonResponse
    {
        try {
            $task = Task::find($id);
            
            if (!$task) {
                return response()->json([
                    'success' => false,
                    'error' => 'Task not found',
                ], 404);
            }

            $result = $request->input('result');
            $completedTask = $this->taskService->completeTask($task, $result);
            
            return response()->json([
                'success' => true,
                'data' => $completedTask,
                'message' => 'Task completed successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to complete task: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * 获取任务详细信息
     */
    public function getTaskDetails(int $id): JsonResponse
    {
        try {
            $task = Task::with(['user', 'agent', 'project', 'parentTask', 'subTasks'])->find($id);
            
            if (!$task) {
                return response()->json([
                    'success' => false,
                    'error' => 'Task not found',
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'task' => $task,
                    'completion_rate' => $task->getCompletionRate(),
                    'sub_tasks_count' => $task->subTasks->count(),
                    'completed_sub_tasks' => $task->subTasks->where('status', Task::STATUS_COMPLETED)->count(),
                    'is_main_task' => $task->isMainTask(),
                    'is_sub_task' => $task->isSubTask(),
                    'is_completed' => $task->isCompleted(),
                    'is_in_progress' => $task->isInProgress(),
                    'is_blocked' => $task->isBlocked(),
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to get task details: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * 创建子任务
     */
    public function createSubTask(int $parentId, Request $request): JsonResponse
    {
        try {
            $parentTask = Task::find($parentId);
            
            if (!$parentTask) {
                return response()->json([
                    'success' => false,
                    'error' => 'Parent task not found',
                ], 404);
            }

            $user = User::find($parentTask->user_id);
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'error' => 'User not found',
                ], 404);
            }

            $data = $request->all();
            $data['parent_task_id'] = $parentId;
            $data['type'] = 'sub';

            $subTask = $this->taskService->create($user, $data);
            
            return response()->json([
                'success' => true,
                'data' => $subTask,
                'message' => 'Sub task created successfully',
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to create sub task: ' . $e->getMessage(),
            ], 500);
        }
    }
}
