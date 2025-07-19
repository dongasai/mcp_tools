<?php

namespace App\Modules\Task\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Modules\Mcp\Tools\TaskTool;
use App\Modules\Mcp\Resources\TaskResource;
use App\Modules\Task\Models\Task;
use App\Modules\User\Models\User;
use App\Modules\Project\Models\Project;

class TaskMcpTestController extends Controller
{
    public function __construct(
        private TaskTool $taskTool,
        private TaskResource $taskResource
    ) {}

    /**
     * 测试 TaskTool 创建主任务
     */
    public function testCreateMainTask(Request $request): JsonResponse
    {
        try {
            // 模拟 Agent 请求头
            $request->headers->set('X-Agent-ID', 'test-agent-001');

            // 获取第一个用户和项目用于测试
            $user = User::first();
            $project = Project::first();

            if (!$user || !$project) {
                return response()->json([
                    'error' => 'No user or project found for testing'
                ], 400);
            }

            $result = $this->taskTool->createMainTask(
                (string)$project->id,
                'MCP 测试主任务',
                '通过 MCP TaskTool 创建的测试任务',
                'medium'
            );

            return response()->json([
                'success' => true,
                'message' => 'TaskTool create_main_task test completed',
                'result' => $result
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ], 500);
        }
    }

    /**
     * 测试 TaskTool 创建子任务
     */
    public function testCreateSubTask(Request $request): JsonResponse
    {
        try {
            // 模拟 Agent 请求头
            $request->headers->set('X-Agent-ID', 'test-agent-001');

            // 获取第一个主任务用于测试
            $parentTask = Task::where('type', 'main')->orderBy('id', 'desc')->first();

            if (!$parentTask) {
                return response()->json([
                    'error' => 'No main task found for testing'
                ], 400);
            }

            $result = $this->taskTool->createSubTask(
                (string)$parentTask->id,
                'MCP 测试子任务',
                '通过 MCP TaskTool 创建的测试子任务',
                'high'
            );

            return response()->json([
                'success' => true,
                'message' => 'TaskTool create_sub_task test completed',
                'result' => $result
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }

    /**
     * 测试 TaskTool 获取任务列表
     */
    public function testListTasks(Request $request): JsonResponse
    {
        try {
            // 模拟 Agent 请求头
            $request->headers->set('X-Agent-ID', 'test-agent-001');

            $result = $this->taskTool->listTasks('pending', '', '');

            return response()->json([
                'success' => true,
                'message' => 'TaskTool list_tasks test completed',
                'result' => $result
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }

    /**
     * 测试 TaskResource 获取任务列表
     */
    public function testResourceList(Request $request): JsonResponse
    {
        try {
            // 模拟 Agent 请求头
            $request->headers->set('X-Agent-ID', 'test-agent-001');

            $result = $this->taskResource->read('task://list');

            return response()->json([
                'success' => true,
                'message' => 'TaskResource list test completed',
                'result' => $result
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }

    /**
     * 测试 TaskResource 获取单个任务
     */
    public function testResourceGet(Request $request): JsonResponse
    {
        try {
            // 模拟 Agent 请求头
            $request->headers->set('X-Agent-ID', 'test-agent-001');

            $task = Task::first();
            if (!$task) {
                return response()->json([
                    'error' => 'No task found for testing'
                ], 400);
            }

            $result = $this->taskResource->read("task://{$task->id}");

            return response()->json([
                'success' => true,
                'message' => 'TaskResource get test completed',
                'result' => $result
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }

    /**
     * 测试 TaskTool 添加评论
     */
    public function testAddComment(Request $request): JsonResponse
    {
        try {
            // 模拟 Agent 请求头
            $request->headers->set('X-Agent-ID', 'test-agent-001');

            $task = Task::first();
            if (!$task) {
                return response()->json([
                    'error' => 'No task found for testing'
                ], 400);
            }

            $result = $this->taskTool->addComment(
                (string)$task->id,
                '这是通过 MCP TaskTool 添加的测试评论',
                'general',
                false
            );

            return response()->json([
                'success' => true,
                'message' => 'TaskTool add_comment test completed',
                'result' => $result
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }

    /**
     * 获取 MCP 工具和资源信息
     */
    public function getMcpInfo(): JsonResponse
    {
        try {
            $taskResourceInfo = [
                'name' => $this->taskResource->getName(),
                'description' => $this->taskResource->getDescription(),
                'uri_template' => $this->taskResource->getUriTemplate()
            ];

            return response()->json([
                'success' => true,
                'task_tool' => [
                    'class' => get_class($this->taskTool),
                    'methods' => [
                        'create_main_task',
                        'create_sub_task',
                        'list_tasks',
                        'get_task',
                        'complete_task',
                        'add_comment',
                        'get_assigned_tasks'
                    ]
                ],
                'task_resource' => $taskResourceInfo
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
    }
}
