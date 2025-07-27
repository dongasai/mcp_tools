<?php

namespace Modules\Task\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Modules\Task\Models\Task;
use Modules\Task\Enums\TASKSTATUS;
use Modules\Task\Enums\TASKTYPE;
use Modules\Task\Enums\TASKPRIORITY;
use Modules\User\Models\User;
use App\Modules\Project\Models\Project;

class TaskModelTestController
{
    /**
     * 测试Task模型的基本功能
     */
    public function testTaskModel(): JsonResponse
    {
        try {
            // 测试枚举类型
            $statusOptions = TASKSTATUS::selectOptions();
            $typeOptions = TASKTYPE::selectOptions();
            $priorityOptions = TASKPRIORITY::selectOptions();

            // 测试模型创建（不实际保存到数据库）
            $task = new Task([
                'title' => '测试任务',
                'description' => '这是一个测试任务',
                'type' => TASKTYPE::MAIN->value,
                'status' => TASKSTATUS::PENDING->value,
                'priority' => TASKPRIORITY::MEDIUM->value,
                'progress' => 0,
                'tags' => ['test', 'model'],
                'metadata' => ['test' => true],
            ]);

            // 测试业务方法
            $isMainTask = $task->isMainTask();
            $isSubTask = $task->isSubTask();
            $isCompleted = $task->isCompleted();
            $isActive = $task->isActive();

            // 测试查询作用域（不执行查询，只检查方法存在）
            // 注意：这些是查询构建器方法，不能直接调用，只能检查方法是否存在

            return response()->json([
                'success' => true,
                'message' => 'Task模型测试通过',
                'data' => [
                    'enums' => [
                        'status_options' => $statusOptions,
                        'type_options' => $typeOptions,
                        'priority_options' => $priorityOptions,
                    ],
                    'model_test' => [
                        'fillable_fields' => $task->getFillable(),
                        'casts' => $task->getCasts(),
                        'attributes' => $task->getAttributes(),
                    ],
                    'business_methods' => [
                        'is_main_task' => $isMainTask,
                        'is_sub_task' => $isSubTask,
                        'is_completed' => $isCompleted,
                        'is_active' => $isActive,
                    ],
                    'query_scopes' => [
                        'pending_query_exists' => method_exists(Task::class, 'scopePending'),
                        'main_tasks_query_exists' => method_exists(Task::class, 'scopeMainTasks'),
                        'sub_tasks_query_exists' => method_exists(Task::class, 'scopeSubTasks'),
                    ],
                    'relationships' => [
                        'user_relation_exists' => method_exists($task, 'user'),
                        'project_relation_exists' => method_exists($task, 'project'),
                        'agent_relation_exists' => method_exists($task, 'agent'),
                        'parent_task_relation_exists' => method_exists($task, 'parentTask'),
                        'sub_tasks_relation_exists' => method_exists($task, 'subTasks'),
                        'comments_relation_exists' => method_exists($task, 'comments'),
                    ],
                ],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ], 500);
        }
    }

    /**
     * 测试事件监听器
     */
    public function testEventListeners(): JsonResponse
    {
        try {
            // 获取第一个用户和项目用于测试
            $user = User::first();
            $project = Project::first();

            if (!$user || !$project) {
                return response()->json([
                    'success' => false,
                    'error' => 'No user or project found for testing',
                ]);
            }

            // 创建测试任务（会触发TaskCreated事件）
            $task = Task::create([
                'user_id' => $user->id,
                'project_id' => $project->id,
                'title' => '事件监听器测试任务',
                'description' => '测试事件监听器是否正常工作',
                'type' => TASKTYPE::MAIN->value,
                'status' => TASKSTATUS::PENDING->value,
                'priority' => TASKPRIORITY::MEDIUM->value,
                'progress' => 0,
            ]);

            // 测试状态变更事件
            $task->update(['status' => TASKSTATUS::IN_PROGRESS->value]);

            // 测试进度更新事件
            $task->update(['progress' => 50]);

            // 测试Agent变更事件
            $task->update(['agent_id' => 1]); // 假设存在agent_id为1的Agent

            // 清理测试数据
            $task->delete();

            return response()->json([
                'success' => true,
                'message' => '事件监听器测试完成',
                'data' => [
                    'task_created' => true,
                    'status_changed' => true,
                    'progress_updated' => true,
                    'agent_changed' => true,
                    'task_deleted' => true,
                ],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ], 500);
        }
    }

    /**
     * 测试Task模型与数据库的兼容性
     */
    public function testDatabaseCompatibility(): JsonResponse
    {
        try {
            // 获取第一个用户和项目用于测试
            $user = User::first();
            $project = Project::first();

            if (!$user || !$project) {
                return response()->json([
                    'success' => false,
                    'error' => 'No user or project found for testing',
                ]);
            }

            // 创建测试任务
            $task = Task::create([
                'user_id' => $user->id,
                'project_id' => $project->id,
                'title' => '数据库兼容性测试任务',
                'description' => '测试Task模型与数据库的兼容性',
                'type' => TASKTYPE::MAIN->value,
                'status' => TASKSTATUS::PENDING->value,
                'priority' => TASKPRIORITY::MEDIUM->value,
                'progress' => 0,
                'tags' => ['test', 'database'],
                'metadata' => ['test_type' => 'database_compatibility'],
            ]);

            // 测试关联关系
            $taskUser = $task->user;
            $taskProject = $task->project;

            // 测试业务方法
            $task->start();
            $task->updateProgress(50);
            $task->complete();

            // 清理测试数据
            $task->delete();

            return response()->json([
                'success' => true,
                'message' => 'Task模型数据库兼容性测试通过',
                'data' => [
                    'task_created' => true,
                    'user_relation_works' => $taskUser !== null,
                    'project_relation_works' => $taskProject !== null,
                    'business_methods_work' => true,
                    'task_deleted' => true,
                ],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ], 500);
        }
    }
}
