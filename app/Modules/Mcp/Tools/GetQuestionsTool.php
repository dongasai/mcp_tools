<?php

namespace App\Modules\Mcp\Tools;

use PhpMcp\Server\Attributes\McpTool;
use App\Modules\Agent\Services\QuestionService;
use App\Modules\Agent\Services\AgentService;
use App\Modules\Core\Contracts\LogInterface;

class GetQuestionsTool
{
    public function __construct(
        private QuestionService $questionService,
        private AgentService $agentService,
        private LogInterface $logger
    ) {}

    /**
     * 获取问题列表，支持多种过滤条件
     */
    #[McpTool(name: 'get_questions')]
    public function getQuestions(
        ?string $status = null,
        ?string $priority = null,
        ?string $question_type = null,
        ?int $task_id = null,
        int $limit = 10,
        bool $only_mine = true,
        bool $include_expired = false
    ): array {
        try {
            // 获取当前Agent信息（这里需要根据实际的认证机制来实现）
            $agentId = $this->getCurrentAgentId();
            if (!$agentId) {
                throw new \Exception('无法获取Agent身份信息');
            }

            $agent = $this->agentService->findByIdentifier($agentId);
            if (!$agent) {
                throw new \Exception('Agent不存在');
            }

            // 检查Agent是否绑定了项目
            if (!$agent->project_id) {
                throw new \Exception('Agent is not bound to any project');
            }

            // 准备过滤条件
            $filters = ['project_id' => $agent->project_id];

            // 默认只返回当前Agent的问题
            if ($only_mine) {
                $filters['agent_id'] = $agent->id;
            }

            // 应用其他过滤条件
            if ($status) {
                $filters['status'] = $status;
            }

            if ($priority) {
                $filters['priority'] = $priority;
            }

            if ($question_type) {
                $filters['question_type'] = $question_type;
            }

            if ($task_id) {
                $filters['task_id'] = $task_id;
            }

            // 过期问题处理
            if (!$include_expired) {
                $filters['not_expired'] = true;
            }

            // 获取问题列表
            $limit = min($limit, 100);
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

            return $result;

        } catch (\Exception $e) {
            $this->logger->error('Failed to get questions', [
                'error' => $e->getMessage(),
                'filters' => $filters ?? [],
            ]);

            return [
                'success' => false,
                'error' => '获取问题列表失败: ' . $e->getMessage()
            ];
        }
    }

    /**
     * 获取当前Agent ID
     */
    private function getCurrentAgentId(): ?string
    {
        // 这里需要从MCP调用上下文中获取Agent ID
        // 具体实现取决于MCP框架如何传递认证信息

        // 临时实现：从请求中获取
        $request = request();
        return $request->header('X-Agent-ID') ?? $request->attributes->get('mcp_agent_id');
    }
}
