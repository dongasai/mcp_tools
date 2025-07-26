<?php

namespace Modules\MCP\Tools;

use PhpMCP\Server\Attributes\MCPTool;
use App\Modules\Agent\Services\QuestionService;
use App\Modules\Agent\Services\AuthenticationService;
use App\Modules\Core\Contracts\LogInterface;
use App\Modules\Agent\Models\AgentQuestion;

class AskQuestionTool
{
    public function __construct(
        private QuestionService $questionService,
        private AuthenticationService $authService,
        private LogInterface $logger
    ) {}

    /**
     * Agent向用户提出问题，等待回答（阻塞式，超时600秒）
     */
    #[MCPTool(name: 'question_ask')]
    public function askQuestion(
        string $title,
        string $content,
        string $priority = 'MEDIUM',
        ?int $task_id = null,
        ?array $context = null,
        int $timeout = 600
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

            // 准备问题数据，设置过期时间为超时时间
            $questionData = [
                'agent_id' => $agent->id,
                'user_id' => $agent->user_id,
                'project_id' => $agent->project_id, // 自动使用Agent绑定的项目
                'title' => $title,
                'content' => $content,
                'priority' => $priority,
                'expires_in' => $timeout, // 使用超时时间作为过期时间
            ];

            // 可选字段
            if ($task_id) {
                $questionData['task_id'] = $task_id;
            }

            if ($context) {
                $questionData['context'] = $context;
            }

            // 创建问题
            $question = $this->questionService->createQuestion($questionData);

            $this->logger->info('Question created via MCP, waiting for answer', [
                'question_id' => $question->id,
                'agent_id' => $agentId,
                'priority' => $question->priority,
                'timeout' => $timeout,
            ]);

            // 阻塞式等待回答
            $startTime = time();
            $pollInterval = 2; // 每2秒检查一次

            while (time() - $startTime < $timeout) {
                // 重新获取问题状态
                $question->refresh();

                // 检查是否已被回答
                if ($question->status === AgentQuestion::STATUS_ANSWERED) {
                    $this->logger->info('Question answered', [
                        'question_id' => $question->id,
                        'agent_id' => $agentId,
                        'answer_time' => time() - $startTime,
                    ]);

                    return [
                        'success' => true,
                        'question_id' => $question->id,
                        'status' => 'ANSWERED',
                        'answer' => $question->answer,
                        'answer_choice' => $question->answer_choice,
                        'answered_at' => $question->answered_at?->toISOString(),
                        'answered_by' => $question->answered_by,
                        'wait_time' => time() - $startTime,
                    ];
                }

                // 检查是否被忽略
                if ($question->status === AgentQuestion::STATUS_IGNORED) {
                    $this->logger->info('Question ignored', [
                        'question_id' => $question->id,
                        'agent_id' => $agentId,
                    ]);

                    return [
                        'success' => false,
                        'question_id' => $question->id,
                        'status' => 'IGNORED',
                        'error' => '问题被用户忽略',
                        'wait_time' => time() - $startTime,
                    ];
                }

                // 等待下次检查
                sleep($pollInterval);
            }

            // 超时处理
            $this->logger->warning('Question timeout', [
                'question_id' => $question->id,
                'agent_id' => $agentId,
                'timeout' => $timeout,
            ]);

            return [
                'success' => false,
                'question_id' => $question->id,
                'status' => 'TIMEOUT',
                'error' => "等待回答超时（{$timeout}秒）",
                'wait_time' => $timeout,
            ];

        } catch (\InvalidArgumentException $e) {
            $this->logger->warning('Invalid question data', [
                'error' => $e->getMessage(),
                'title' => $title,
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];

        } catch (\Exception $e) {
            $this->logger->error('Failed to create question', [
                'error' => $e->getMessage(),
                'title' => $title,
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
