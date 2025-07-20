<?php

namespace App\Modules\Mcp\Tools;

use App\Modules\Agent\Services\QuestionService;
use App\Modules\Agent\Services\AgentService;
use App\Modules\Core\Contracts\LogInterface;
use PhpMcp\Laravel\Contracts\ToolInterface;
use PhpMcp\Laravel\Contracts\ToolCallInterface;
use PhpMcp\Laravel\Contracts\ToolResultInterface;
use PhpMcp\Laravel\ToolResult;
use PhpMcp\Laravel\TextContent;

class GetQuestionsTool implements ToolInterface
{
    public function __construct(
        private QuestionService $questionService,
        private AgentService $agentService,
        private LogInterface $logger
    ) {}

    public function getName(): string
    {
        return 'get_questions';
    }

    public function getDescription(): string
    {
        return '获取问题列表，支持多种过滤条件';
    }

    public function getInputSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'status' => [
                    'type' => 'string',
                    'enum' => ['PENDING', 'ANSWERED', 'IGNORED'],
                    'description' => '问题状态过滤',
                ],
                'priority' => [
                    'type' => 'string',
                    'enum' => ['URGENT', 'HIGH', 'MEDIUM', 'LOW'],
                    'description' => '优先级过滤',
                ],
                'question_type' => [
                    'type' => 'string',
                    'enum' => ['CHOICE', 'FEEDBACK'],
                    'description' => '问题类型过滤',
                ],
                'task_id' => [
                    'type' => 'integer',
                    'description' => '任务ID过滤',
                ],
                'project_id' => [
                    'type' => 'integer',
                    'description' => '项目ID过滤',
                ],
                'limit' => [
                    'type' => 'integer',
                    'description' => '返回数量限制',
                    'default' => 10,
                    'minimum' => 1,
                    'maximum' => 100,
                ],
                'only_mine' => [
                    'type' => 'boolean',
                    'description' => '是否只返回当前Agent的问题',
                    'default' => true,
                ],
                'include_expired' => [
                    'type' => 'boolean',
                    'description' => '是否包含已过期的问题',
                    'default' => false,
                ],
            ],
        ];
    }

    public function call(ToolCallInterface $call): ToolResultInterface
    {
        try {
            $arguments = $call->getArguments();
            
            // 获取当前Agent信息
            $agentId = $this->getAgentIdFromCall($call);
            if (!$agentId) {
                return new ToolResult([
                    new TextContent('错误：无法获取Agent身份信息')
                ], true);
            }

            $agent = $this->agentService->findByIdentifier($agentId);
            if (!$agent) {
                return new ToolResult([
                    new TextContent('错误：Agent不存在')
                ], true);
            }

            // 准备过滤条件
            $filters = [];

            // 默认只返回当前Agent的问题
            if ($arguments['only_mine'] ?? true) {
                $filters['agent_id'] = $agent->id;
            }

            // 应用其他过滤条件
            if (isset($arguments['status'])) {
                $filters['status'] = $arguments['status'];
            }

            if (isset($arguments['priority'])) {
                $filters['priority'] = $arguments['priority'];
            }

            if (isset($arguments['question_type'])) {
                $filters['question_type'] = $arguments['question_type'];
            }

            if (isset($arguments['task_id'])) {
                $filters['task_id'] = $arguments['task_id'];
            }

            if (isset($arguments['project_id'])) {
                $filters['project_id'] = $arguments['project_id'];
            }

            // 过期问题处理
            if (!($arguments['include_expired'] ?? false)) {
                $filters['not_expired'] = true;
            }

            // 获取问题列表
            $limit = min($arguments['limit'] ?? 10, 100);
            $questions = $this->questionService->getQuestions($filters, $limit);

            // 格式化返回数据
            $result = [
                'success' => true,
                'total' => $questions->total(),
                'per_page' => $questions->perPage(),
                'current_page' => $questions->currentPage(),
                'questions' => $questions->items()->map(function ($question) {
                    return [
                        'id' => $question->id,
                        'title' => $question->title,
                        'content' => $question->content,
                        'question_type' => $question->question_type,
                        'question_type_label' => $question->getTypeLabel(),
                        'priority' => $question->priority,
                        'priority_label' => $question->getPriorityLabel(),
                        'status' => $question->status,
                        'status_label' => $question->getStatusLabel(),
                        'answer' => $question->answer,
                        'answer_type' => $question->answer_type,
                        'answer_options' => $question->answer_options,
                        'context' => $question->context,
                        'task_id' => $question->task_id,
                        'project_id' => $question->project_id,
                        'created_at' => $question->created_at->toISOString(),
                        'answered_at' => $question->answered_at?->toISOString(),
                        'expires_at' => $question->expires_at?->toISOString(),
                        'is_expired' => $question->isExpired(),
                        'agent' => [
                            'id' => $question->agent->id,
                            'identifier' => $question->agent->identifier,
                            'name' => $question->agent->name,
                        ],
                        'user' => [
                            'id' => $question->user->id,
                            'name' => $question->user->name,
                            'email' => $question->user->email,
                        ],
                        'answered_by' => $question->answeredBy ? [
                            'id' => $question->answeredBy->id,
                            'name' => $question->answeredBy->name,
                            'email' => $question->answeredBy->email,
                        ] : null,
                    ];
                })->toArray(),
            ];

            $this->logger->info('Questions retrieved via MCP', [
                'agent_id' => $agentId,
                'filters' => $filters,
                'count' => count($result['questions']),
            ]);

            return new ToolResult([
                new TextContent(json_encode($result, JSON_UNESCAPED_UNICODE))
            ]);

        } catch (\Exception $e) {
            $this->logger->error('Failed to get questions', [
                'error' => $e->getMessage(),
                'arguments' => $arguments ?? [],
            ]);

            return new ToolResult([
                new TextContent('错误：获取问题列表失败 - ' . $e->getMessage())
            ], true);
        }
    }

    /**
     * 从调用中获取Agent ID
     */
    private function getAgentIdFromCall(ToolCallInterface $call): ?string
    {
        // 这里需要从MCP调用上下文中获取Agent ID
        // 具体实现取决于MCP框架如何传递认证信息
        
        // 临时实现：从请求中获取
        $request = request();
        return $request->header('X-Agent-ID') ?? $request->attributes->get('mcp_agent_id');
    }
}
