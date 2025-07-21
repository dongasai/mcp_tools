<?php

namespace App\Modules\Mcp\Tools;

use PhpMcp\Server\Attributes\McpTool;
use App\Modules\Agent\Services\QuestionService;
use App\Modules\Agent\Services\AuthenticationService;
use App\Modules\Core\Contracts\LogInterface;

class AskQuestionTool
{
    public function __construct(
        private QuestionService $questionService,
        private AuthenticationService $authService,
        private LogInterface $logger
    ) {}

    /**
     * Agent向用户提出问题，获取指导、确认或澄清
     */
    #[McpTool(name: 'ask_question')]
    public function askQuestion(
        string $title,
        string $content,
        string $question_type,
        string $priority = 'MEDIUM',
        ?int $task_id = null,
        ?array $context = null,
        ?array $answer_options = null,
        int $expires_in = 3600
    ): array
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

            // 检查Agent是否绑定了项目（自动使用Agent绑定的项目）
            if (!$agent->project_id) {
                throw new \Exception('Agent is not bound to any project');
            }

            // 准备问题数据
            $questionData = [
                'agent_id' => $agent->id,
                'user_id' => $agent->user_id,
                'project_id' => $agent->project_id, // 自动使用Agent绑定的项目
                'title' => $title,
                'content' => $content,
                'question_type' => $question_type,
                'priority' => $priority,
            ];

            // 可选字段
            if ($task_id) {
                $questionData['task_id'] = $task_id;
            }

            if ($context) {
                $questionData['context'] = $context;
            }

            if ($answer_options) {
                $questionData['answer_options'] = $answer_options;
            }

            if ($expires_in) {
                $questionData['expires_in'] = $expires_in;
            }

            // 创建问题
            $question = $this->questionService->createQuestion($questionData);

            $this->logger->info('Question created via MCP', [
                'question_id' => $question->id,
                'agent_id' => $agentId,
                'question_type' => $question->question_type,
                'priority' => $question->priority,
            ]);

            // 返回成功结果
            return [
                'success' => true,
                'question_id' => $question->id,
                'status' => $question->status,
                'created_at' => $question->created_at->toISOString(),
                'expires_at' => $question->expires_at?->toISOString(),
                'message' => '问题已成功创建，等待用户回答',
            ];

        } catch (\InvalidArgumentException $e) {
            $this->logger->warning('Invalid question data', [
                'error' => $e->getMessage(),
                'title' => $title,
                'question_type' => $question_type,
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];

        } catch (\Exception $e) {
            $this->logger->error('Failed to create question', [
                'error' => $e->getMessage(),
                'title' => $title,
                'question_type' => $question_type,
            ]);

            return [
                'success' => false,
                'error' => '创建问题失败: ' . $e->getMessage()
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
