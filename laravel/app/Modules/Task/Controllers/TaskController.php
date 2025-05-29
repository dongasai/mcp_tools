<?php

namespace App\Modules\Task\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use App\Modules\Task\Services\TaskService;
use App\Modules\Task\Models\Task;
use App\Modules\Core\Contracts\LogInterface;

class TaskController extends Controller
{
    protected TaskService $taskService;
    protected LogInterface $logger;

    public function __construct(TaskService $taskService, LogInterface $logger)
    {
        $this->taskService = $taskService;
        $this->logger = $logger;
    }

    /**
     * 获取任务列表
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $user = auth()->user();
            
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'error' => 'Unauthenticated',
                ], 401);
            }

            $filters = $request->only([
                'status', 'type', 'priority', 'project_id', 'agent_id', 
                'main_tasks_only', 'sub_tasks_only', 'search', 'due_soon', 'overdue'
            ]);
            $tasks = $this->taskService->getUserTasks($user, $filters);

            return response()->json([
                'success' => true,
                'data' => $tasks,
                'count' => $tasks->count(),
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Failed to get tasks', [
                'user_id' => auth()->id(),
                'error' => $e->getMessage(),
                'filters' => $request->all(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to retrieve tasks',
            ], 500);
        }
    }

    /**
     * 创建任务
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $user = auth()->user();
            
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'error' => 'Unauthenticated',
                ], 401);
            }

            $task = $this->taskService->create($user, $request->all());

            return response()->json([
                'success' => true,
                'data' => $task,
                'message' => 'Task created successfully',
            ], 201);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 400);
        } catch (\Exception $e) {
            $this->logger->error('Failed to create task', [
                'user_id' => auth()->id(),
                'error' => $e->getMessage(),
                'data' => $request->all(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to create task',
            ], 500);
        }
    }

    /**
     * 获取单个任务
     */
    public function show(Task $task): JsonResponse
    {
        try {
            $user = auth()->user();
            
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'error' => 'Unauthenticated',
                ], 401);
            }

            // 检查权限
            if ($task->user_id !== $user->id) {
                return response()->json([
                    'success' => false,
                    'error' => 'Unauthorized',
                ], 403);
            }

            $task->load(['user', 'agent', 'project', 'parentTask', 'subTasks']);

            return response()->json([
                'success' => true,
                'data' => [
                    'task' => $task,
                    'completion_rate' => $task->getCompletionRate(),
                    'sub_tasks_count' => $task->subTasks->count(),
                    'completed_sub_tasks' => $task->subTasks->where('status', Task::STATUS_COMPLETED)->count(),
                ],
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Failed to get task', [
                'user_id' => auth()->id(),
                'task_id' => $task->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to retrieve task',
            ], 500);
        }
    }

    /**
     * 更新任务
     */
    public function update(Request $request, Task $task): JsonResponse
    {
        try {
            $user = auth()->user();
            
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'error' => 'Unauthenticated',
                ], 401);
            }

            // 检查权限
            if ($task->user_id !== $user->id) {
                return response()->json([
                    'success' => false,
                    'error' => 'Unauthorized',
                ], 403);
            }

            $updatedTask = $this->taskService->update($task, $request->all());

            return response()->json([
                'success' => true,
                'data' => $updatedTask,
                'message' => 'Task updated successfully',
            ]);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 400);
        } catch (\Exception $e) {
            $this->logger->error('Failed to update task', [
                'user_id' => auth()->id(),
                'task_id' => $task->id,
                'error' => $e->getMessage(),
                'data' => $request->all(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to update task',
            ], 500);
        }
    }

    /**
     * 删除任务
     */
    public function destroy(Task $task): JsonResponse
    {
        try {
            $user = auth()->user();
            
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'error' => 'Unauthenticated',
                ], 401);
            }

            // 检查权限
            if ($task->user_id !== $user->id) {
                return response()->json([
                    'success' => false,
                    'error' => 'Unauthorized',
                ], 403);
            }

            $this->taskService->delete($task);

            return response()->json([
                'success' => true,
                'message' => 'Task deleted successfully',
            ]);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 400);
        } catch (\Exception $e) {
            $this->logger->error('Failed to delete task', [
                'user_id' => auth()->id(),
                'task_id' => $task->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to delete task',
            ], 500);
        }
    }

    /**
     * 开始任务
     */
    public function start(Task $task): JsonResponse
    {
        try {
            $user = auth()->user();
            
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'error' => 'Unauthenticated',
                ], 401);
            }

            // 检查权限
            if ($task->user_id !== $user->id) {
                return response()->json([
                    'success' => false,
                    'error' => 'Unauthorized',
                ], 403);
            }

            $startedTask = $this->taskService->startTask($task);

            return response()->json([
                'success' => true,
                'data' => $startedTask,
                'message' => 'Task started successfully',
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Failed to start task', [
                'user_id' => auth()->id(),
                'task_id' => $task->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to start task',
            ], 500);
        }
    }

    /**
     * 完成任务
     */
    public function complete(Request $request, Task $task): JsonResponse
    {
        try {
            $user = auth()->user();
            
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'error' => 'Unauthenticated',
                ], 401);
            }

            // 检查权限
            if ($task->user_id !== $user->id) {
                return response()->json([
                    'success' => false,
                    'error' => 'Unauthorized',
                ], 403);
            }

            $result = $request->input('result');
            $completedTask = $this->taskService->completeTask($task, $result);

            return response()->json([
                'success' => true,
                'data' => $completedTask,
                'message' => 'Task completed successfully',
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Failed to complete task', [
                'user_id' => auth()->id(),
                'task_id' => $task->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to complete task',
            ], 500);
        }
    }

    /**
     * 获取任务的子任务
     */
    public function subTasks(Task $task): JsonResponse
    {
        try {
            $user = auth()->user();
            
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'error' => 'Unauthenticated',
                ], 401);
            }

            // 检查权限
            if ($task->user_id !== $user->id) {
                return response()->json([
                    'success' => false,
                    'error' => 'Unauthorized',
                ], 403);
            }

            $subTasks = $task->subTasks()->with(['agent', 'project'])->get();

            return response()->json([
                'success' => true,
                'data' => $subTasks,
                'count' => $subTasks->count(),
                'completed_count' => $subTasks->where('status', Task::STATUS_COMPLETED)->count(),
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Failed to get sub tasks', [
                'user_id' => auth()->id(),
                'task_id' => $task->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to retrieve sub tasks',
            ], 500);
        }
    }
}
