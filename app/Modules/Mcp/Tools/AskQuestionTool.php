<?php

namespace App\Modules\Mcp\Tools;

use PhpMcp\Server\Attributes\McpTool;
use PhpMcp\Server\ToolCallInterface;
use PhpMcp\Server\ToolResultInterface;
use PhpMcp\Server\ToolResult;
use PhpMcp\Server\Content\TextContent;
use App\Modules\Agent\Services\QuestionService;
use App\Modules\Agent\Services\AgentService;
use App\Modules\Core\Contracts\LogInterface;

class AskQuestionTool
{
    public function __construct(
        private QuestionService $questionService,
        private AgentService $agentService,
        private LogInterface $logger
    ) {}

    public function getName(): string
    {
        return 'ask_question';
    }

    public function getDescription(): string
    {
        return 'Agent向用户提出问题，获取指导、确认或澄清';
    }

    public function getInputSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'title' => [
                    'type' => 'string',
                    'description' => '问题标题',
                    'maxLength' => 255,
                ],
                'content' => [
                    'type' => 'string',
                    'description' => '详细问题描述',
                ],
                'question_type' => [
                    'type' => 'string',
                    'enum' => ['CHOICE', 'FEEDBACK'],
                    'description' => '问题类型：CHOICE(选择类) 或 FEEDBACK(反馈类)',
                ],
                'priority' => [
                    'type' => 'string',
                    'enum' => ['URGENT', 'HIGH', 'MEDIUM', 'LOW'],
                    'description' => '问题优先级',
                    'default' => 'MEDIUM',
                ],
                'task_id' => [
                    'type' => 'integer',
                    'description' => '关联的任务ID（可选）',
                ],
                'project_id' => [
                    'type' => 'integer',
                    'description' => '关联的项目ID（可选）',
                ],
                'context' => [
                    'type' => 'object',
                    'description' => '问题上下文信息（可选）',
                ],
                'answer_options' => [
                    'type' => 'array',
                    'items' => ['type' => 'string'],
                    'description' => '可选答案列表（选择类问题使用）',
                ],
                'expires_in' => [
                    'type' => 'integer',
                    'description' => '过期时间（秒），默认1小时',
                    'default' => 3600,
                ],
            ],
            'required' => ['title', 'content', 'question_type'],
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

            // 准备问题数据
            $questionData = [
                'agent_id' => $agent->id,
                'user_id' => $agent->user_id,
                'title' => $arguments['title'],
                'content' => $arguments['content'],
                'question_type' => $arguments['question_type'],
                'priority' => $arguments['priority'] ?? 'MEDIUM',
            ];

            // 可选字段
            if (isset($arguments['task_id'])) {
                $questionData['task_id'] = $arguments['task_id'];
            }

            if (isset($arguments['project_id'])) {
                $questionData['project_id'] = $arguments['project_id'];
            }

            if (isset($arguments['context'])) {
                $questionData['context'] = $arguments['context'];
            }

            if (isset($arguments['answer_options'])) {
                $questionData['answer_options'] = $arguments['answer_options'];
            }

            if (isset($arguments['expires_in'])) {
                $questionData['expires_in'] = $arguments['expires_in'];
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
            $result = [
                'success' => true,
                'question_id' => $question->id,
                'status' => $question->status,
                'created_at' => $question->created_at->toISOString(),
                'expires_at' => $question->expires_at?->toISOString(),
                'message' => '问题已成功创建，等待用户回答',
            ];

            return new ToolResult([
                new TextContent(json_encode($result, JSON_UNESCAPED_UNICODE))
            ]);

        } catch (\InvalidArgumentException $e) {
            $this->logger->warning('Invalid question data', [
                'error' => $e->getMessage(),
                'arguments' => $arguments ?? [],
            ]);

            return new ToolResult([
                new TextContent('错误：' . $e->getMessage())
            ], true);

        } catch (\Exception $e) {
            $this->logger->error('Failed to create question', [
                'error' => $e->getMessage(),
                'arguments' => $arguments ?? [],
            ]);

            return new ToolResult([
                new TextContent('错误：创建问题失败 - ' . $e->getMessage())
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
