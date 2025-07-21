<?php

namespace App\Modules\Mcp\Tools;

use PhpMcp\Server\Attributes\McpTool;
use App\Modules\Agent\Services\QuestionService;
use App\Modules\Agent\Services\AuthenticationService;
use App\Modules\Core\Contracts\LogInterface;

class CheckAnswerTool
{
    public function __construct(
        private QuestionService $questionService,
        private AuthenticationService $authService,
        private LogInterface $logger
    ) {}

    /**
     * 检查问题是否已被回答，获取问题的当前状态和回答内容
     */
    #[McpTool(name: 'check_answer')]
    public function checkAnswer(int $question_id): array
    {
        try {
            // 获取当前Agent信息
            $agentId = $this->getCurrentAgentId();
            if (!$agentId) {
                throw new \Exception('无法获取Agent身份信息');
            }

            $agent = $this->authService->findByAgentId($agentId);
            if (!$agent) {
                throw new \Exception('Agent不存在');
            }

            // 获取问题
            $question = $this->questionService->getQuestionById($question_id);
            if (!$question) {
                throw new \Exception('问题不存在');
            }

            // 检查权限：只能查看自己创建的问题
            if ($question->agent_id !== $agent->id) {
                throw new \Exception('无权限访问此问题');
            }

            $this->logger->info('Question status checked via MCP', [
                'question_id' => $question->id,
                'agent_id' => $agentId,
                'status' => $question->status,
            ]);

            // 返回问题状态和回答
            return [
                'success' => true,
                'question_id' => $question->id,
                'title' => $question->title,
                'content' => $question->content,
                'status' => $question->status,
                'question_type' => $question->question_type,
                'priority' => $question->priority,
                'answer' => $question->answer,
                'answer_choice' => $question->answer_choice,
                'answered_at' => $question->answered_at?->toISOString(),
                'answered_by' => $question->answered_by,
                'created_at' => $question->created_at->toISOString(),
                'expires_at' => $question->expires_at?->toISOString(),
                'is_expired' => $question->isExpired(),
                'context' => $question->context,
            ];

        } catch (\Exception $e) {
            $this->logger->error('Failed to check question answer', [
                'error' => $e->getMessage(),
                'question_id' => $question_id,
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * 获取当前Agent ID
     */
    private function getCurrentAgentId(): ?string
    {
        $request = request();
        return $request->header('X-Agent-ID') ?? $request->attributes->get('mcp_agent_id');
    }
}
