<?php

namespace App\Modules\Mcp\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Modules\Mcp\Services\McpService;
use App\Modules\Core\Services\LogService;
use App\Modules\Mcp\Tools\ProjectTool;
use App\Modules\Mcp\Tools\TaskTool;
use App\Modules\Mcp\Tools\AgentTool;
use App\Modules\Mcp\Tools\AskQuestionTool;
use App\Modules\Mcp\Tools\GetQuestionsTool;
use App\Modules\Mcp\Tools\QuestionBatchTool;

class ToolController extends Controller
{
    public function __construct(
        private McpService $mcpService,
        private LogService $logger,
        private ProjectTool $projectTool,
        private TaskTool $taskTool,
        private AgentTool $agentTool,
        private AskQuestionTool $askQuestionTool,
        private GetQuestionsTool $getQuestionsTool,
        private QuestionBatchTool $questionBatchTool
    ) {}

    /**
     * 获取工具列表
     */
    public function list(Request $request): JsonResponse
    {
        try {
            $agentId = $request->attributes->get('mcp_agent_id');

            // 记录操作
            $this->mcpService->logSession($agentId, 'tool_list');

            // 获取配置的工具列表
            $tools = config('mcp.tools', []);

            $toolList = [];
            foreach ($tools as $name => $config) {
                // 获取工具实例以获取详细信息
                $toolInstance = $this->getToolInstance($name);

                if ($toolInstance) {
                    $toolList[] = [
                        'name' => $name,
                        'description' => $toolInstance->getDescription(),
                        'input_schema' => $toolInstance->getInputSchema(),
                        'class' => $config['class'] ?? ''
                    ];
                } else {
                    $toolList[] = [
                        'name' => $name,
                        'description' => $config['description'] ?? '',
                        'input_schema' => [],
                        'class' => $config['class'] ?? ''
                    ];
                }
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'tools' => $toolList,
                    'count' => count($toolList)
                ]
            ]);

        } catch (\Exception $e) {
            $this->logger->error('Failed to list tools', [
                'error' => $e->getMessage(),
                'agent_id' => $request->attributes->get('mcp_agent_id')
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to list tools: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * 调用指定工具
     */
    public function call(Request $request, string $tool): JsonResponse
    {
        try {
            $agentId = $request->attributes->get('mcp_agent_id');
            $arguments = $request->input('arguments', []);

            // 验证权限
            if (!$this->mcpService->validateAgentAccess($agentId, 'tool', 'call')) {
                return response()->json([
                    'success' => false,
                    'error' => 'Access denied for tool call'
                ], 403);
            }

            // 记录操作
            $this->mcpService->logSession($agentId, 'tool_call', [
                'tool' => $tool,
                'arguments' => $arguments
            ]);

            // 获取工具实例
            $toolInstance = $this->getToolInstance($tool);

            if (!$toolInstance) {
                return response()->json([
                    'success' => false,
                    'error' => 'Tool not found: ' . $tool
                ], 404);
            }

            // 验证参数
            $validationResult = $this->validateToolArguments($toolInstance, $arguments);
            if (!$validationResult['valid']) {
                return response()->json([
                    'success' => false,
                    'error' => 'Invalid arguments: ' . $validationResult['message']
                ], 400);
            }

            // 调用工具
            $result = $toolInstance->call($arguments);

            return response()->json([
                'success' => true,
                'data' => $result
            ]);

        } catch (\Exception $e) {
            $this->logger->error('Failed to call tool', [
                'tool' => $tool,
                'error' => $e->getMessage(),
                'agent_id' => $request->attributes->get('mcp_agent_id')
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to call tool: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * 获取工具实例
     */
    private function getToolInstance(string $toolName): ?object
    {
        return match ($toolName) {
            'project_manager' => $this->projectTool,
            'task_manager' => $this->taskTool,
            'agent_manager' => $this->agentTool,
            'ask_question' => $this->askQuestionTool,
            'get_questions' => $this->getQuestionsTool,
            'question_batch' => $this->questionBatchTool,
            default => null
        };
    }

    /**
     * 验证工具参数
     */
    private function validateToolArguments(object $toolInstance, array $arguments): array
    {
        try {
            // 获取工具的输入模式
            $inputSchema = $toolInstance->getInputSchema();

            // 检查必需参数
            if (isset($inputSchema['required'])) {
                foreach ($inputSchema['required'] as $requiredField) {
                    if (!isset($arguments[$requiredField])) {
                        return [
                            'valid' => false,
                            'message' => "Missing required parameter: {$requiredField}"
                        ];
                    }
                }
            }

            // 检查参数类型（基础验证）
            if (isset($inputSchema['properties'])) {
                foreach ($arguments as $key => $value) {
                    if (isset($inputSchema['properties'][$key])) {
                        $propertySchema = $inputSchema['properties'][$key];

                        // 检查枚举值
                        if (isset($propertySchema['enum']) && !in_array($value, $propertySchema['enum'])) {
                            return [
                                'valid' => false,
                                'message' => "Invalid value for {$key}. Must be one of: " . implode(', ', $propertySchema['enum'])
                            ];
                        }

                        // 检查基础类型
                        if (isset($propertySchema['type'])) {
                            $expectedType = $propertySchema['type'];
                            $actualType = gettype($value);

                            // 类型映射
                            $typeMap = [
                                'string' => 'string',
                                'integer' => 'integer',
                                'number' => ['integer', 'double'],
                                'boolean' => 'boolean',
                                'array' => 'array',
                                'object' => 'array' // JSON对象在PHP中是数组
                            ];

                            if (isset($typeMap[$expectedType])) {
                                $validTypes = is_array($typeMap[$expectedType]) ? $typeMap[$expectedType] : [$typeMap[$expectedType]];

                                if (!in_array($actualType, $validTypes)) {
                                    return [
                                        'valid' => false,
                                        'message' => "Invalid type for {$key}. Expected {$expectedType}, got {$actualType}"
                                    ];
                                }
                            }
                        }
                    }
                }
            }

            return [
                'valid' => true,
                'message' => 'Arguments are valid'
            ];

        } catch (\Exception $e) {
            return [
                'valid' => false,
                'message' => 'Validation error: ' . $e->getMessage()
            ];
        }
    }
}
