<?php

namespace App\Modules\Task\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Task\Models\Task;
use App\Modules\Task\Services\TaskWorkflowService;
use App\Modules\Task\Enums\TASKSTATUS;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

/**
 * 任务工作流测试控制器
 * 
 * 用于测试任务状态机和工作流功能
 */
class TaskWorkflowTestController extends Controller
{
    protected TaskWorkflowService $workflowService;

    public function __construct(TaskWorkflowService $workflowService)
    {
        $this->workflowService = $workflowService;
    }

    /**
     * 测试状态机基础功能
     */
    public function testStateMachine(Request $request): JsonResponse
    {
        try {
            $taskId = $request->input('task_id');
            $task = Task::findOrFail($taskId);

            $result = [
                'task_id' => $task->id,
                'current_status' => $task->status->value,
                'current_status_label' => $task->status->label(),
                'available_transitions' => $this->workflowService->getAvailableTransitions($task),
                'workflow_health' => $this->workflowService->checkWorkflowHealth($task),
            ];

            return response()->json([
                'success' => true,
                'data' => $result,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * 测试状态转换
     */
    public function testTransition(Request $request): JsonResponse
    {
        try {
            $taskId = $request->input('task_id');
            $toStatus = $request->input('to_status');
            $task = Task::findOrFail($taskId);

            // 验证状态
            $statusEnum = TASKSTATUS::from($toStatus);
            
            // 验证转换
            $validation = $this->workflowService->validateTransition($task, $statusEnum);
            
            if (!$validation['valid']) {
                return response()->json([
                    'success' => false,
                    'error' => '状态转换验证失败',
                    'validation' => $validation,
                ], 400);
            }

            // 执行转换
            $success = $this->workflowService->transition($task, $statusEnum, [
                'test_mode' => true,
                'initiated_by' => 'test_controller',
            ]);

            return response()->json([
                'success' => $success,
                'data' => [
                    'task_id' => $task->id,
                    'old_status' => $request->input('old_status', 'unknown'),
                    'new_status' => $task->fresh()->status->value,
                    'validation' => $validation,
                ],
                'errors' => $success ? [] : $this->workflowService->getTransitionErrors($task, $statusEnum),
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * 测试子任务完成规则
     */
    public function testSubTaskCompletion(Request $request): JsonResponse
    {
        try {
            $parentTaskId = $request->input('parent_task_id');
            $parentTask = Task::findOrFail($parentTaskId);

            if (!$parentTask->isMainTask()) {
                return response()->json([
                    'success' => false,
                    'error' => '指定的任务不是主任务',
                ], 400);
            }

            $subTasks = $parentTask->subTasks;
            $completedSubTasks = $subTasks->where('status', TASKSTATUS::COMPLETED);
            $canComplete = $this->workflowService->canTransition($parentTask, TASKSTATUS::COMPLETED);

            return response()->json([
                'success' => true,
                'data' => [
                    'parent_task_id' => $parentTask->id,
                    'parent_status' => $parentTask->status->value,
                    'total_sub_tasks' => $subTasks->count(),
                    'completed_sub_tasks' => $completedSubTasks->count(),
                    'can_complete_parent' => $canComplete,
                    'sub_tasks_status' => $subTasks->map(function ($task) {
                        return [
                            'id' => $task->id,
                            'title' => $task->title,
                            'status' => $task->status->value,
                        ];
                    }),
                    'validation' => $this->workflowService->validateTransition($parentTask, TASKSTATUS::COMPLETED),
                ],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * 测试自动完成父任务
     */
    public function testAutoCompleteParent(Request $request): JsonResponse
    {
        try {
            $subTaskId = $request->input('sub_task_id');
            $subTask = Task::findOrFail($subTaskId);

            if (!$subTask->isSubTask()) {
                return response()->json([
                    'success' => false,
                    'error' => '指定的任务不是子任务',
                ], 400);
            }

            $result = $this->workflowService->autoCompleteParentTask($subTask);

            return response()->json([
                'success' => true,
                'data' => [
                    'sub_task_id' => $subTask->id,
                    'parent_task_id' => $subTask->parent_task_id,
                    'auto_complete_result' => $result,
                    'parent_status_after' => $subTask->parentTask->fresh()->status->value,
                ],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * 获取所有任务状态选项
     */
    public function getStatusOptions(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => TASKSTATUS::options(),
        ]);
    }

    /**
     * 批量测试状态转换
     */
    public function batchTest(Request $request): JsonResponse
    {
        try {
            $taskIds = $request->input('task_ids', []);
            $toStatus = $request->input('to_status');
            
            $tasks = Task::whereIn('id', $taskIds)->get();
            $statusEnum = TASKSTATUS::from($toStatus);

            $results = $this->workflowService->batchTransition($tasks->toArray(), $statusEnum, [
                'test_mode' => true,
                'batch_operation' => true,
            ]);

            return response()->json([
                'success' => true,
                'data' => [
                    'total_tasks' => count($taskIds),
                    'target_status' => $toStatus,
                    'results' => $results,
                    'summary' => [
                        'successful' => count(array_filter($results, fn($r) => $r['success'])),
                        'failed' => count(array_filter($results, fn($r) => !$r['success'])),
                    ],
                ],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 400);
        }
    }
}
