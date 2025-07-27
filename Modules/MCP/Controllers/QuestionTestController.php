<?php

namespace Modules\MCP\Controllers;

use Modules\Agent\Enums\QuestionPriority;
use Modules\Agent\Services\QuestionService;
use Modules\Agent\Services\QuestionNotificationService;
use Modules\Agent\Models\Agent;
use Modules\Agent\Models\AgentQuestion;
use Modules\User\Models\User;
use App\Modules\Core\Controllers\BaseController;
use Illuminate\Http\JsonResponse;

class QuestionTestController extends BaseController
{
    public function __construct(
        private QuestionService $questionService,
        private QuestionNotificationService $notificationService
    ) {}

    /**
     * 测试创建问题
     */
    public function testCreate(): JsonResponse
    {
        try {
            // 获取第一个用户和Agent
            $user = User::first();
            $agent = Agent::first();

            if (!$user || !$agent) {
                return $this->error('需要先创建用户和Agent');
            }

            // 创建测试问题
            $questionData = [
                'agent_id' => $agent->id,
                'user_id' => $user->id,
                'title' => '测试问题 - ' . now()->format('Y-m-d H:i:s'),
                'content' => '这是一个通过API创建的测试问题。请问您希望我如何处理这个任务？',
                'question_type' => AgentQuestion::TYPE_FEEDBACK,
                'priority' => QuestionPriority::HIGH->value,
                'context' => [
                    'source' => 'api_test',
                    'timestamp' => now()->toISOString(),
                    'test_data' => true,
                ],
                'answer_options' => [
                    '继续执行',
                    '暂停等待',
                    '修改方案',
                ],
                'expires_in' => 3600, // 1小时后过期
            ];

            $question = $this->questionService->createQuestion($questionData);

            return $this->success([
                'question' => $question,
                'message' => '测试问题创建成功',
            ]);

        } catch (\Exception $e) {
            return $this->error('创建问题失败: ' . $e->getMessage());
        }
    }

    /**
     * 测试获取问题列表
     */
    public function testList(): JsonResponse
    {
        try {
            $questions = $this->questionService->getQuestions([], 10);

            return $this->success([
                'questions' => $questions->items(),
                'total' => $questions->total(),
                'message' => '获取问题列表成功',
            ]);

        } catch (\Exception $e) {
            return $this->error('获取问题列表失败: ' . $e->getMessage());
        }
    }

    /**
     * 测试回答问题
     */
    public function testAnswer(int $questionId): JsonResponse
    {
        try {
            $user = User::first();
            if (!$user) {
                return $this->error('需要先创建用户');
            }

            $success = $this->questionService->answerQuestion(
                $questionId,
                '我选择继续执行当前方案，请按照原计划进行。',
                AgentQuestion::ANSWER_TYPE_TEXT,
                $user->id
            );

            if (!$success) {
                return $this->error('回答问题失败');
            }

            $question = $this->questionService->getQuestionById($questionId);

            return $this->success([
                'question' => $question,
                'message' => '问题回答成功',
            ]);

        } catch (\Exception $e) {
            return $this->error('回答问题失败: ' . $e->getMessage());
        }
    }

    /**
     * 测试忽略问题
     */
    public function testIgnore(int $questionId): JsonResponse
    {
        try {
            $success = $this->questionService->ignoreQuestion($questionId);

            if (!$success) {
                return $this->error('忽略问题失败');
            }

            $question = $this->questionService->getQuestionById($questionId);

            return $this->success([
                'question' => $question,
                'message' => '问题已忽略',
            ]);

        } catch (\Exception $e) {
            return $this->error('忽略问题失败: ' . $e->getMessage());
        }
    }

    /**
     * 测试获取问题统计
     */
    public function testStats(): JsonResponse
    {
        try {
            $stats = $this->questionService->getQuestionStats();

            return $this->success([
                'stats' => $stats,
                'message' => '获取统计信息成功',
            ]);

        } catch (\Exception $e) {
            return $this->error('获取统计信息失败: ' . $e->getMessage());
        }
    }

    /**
     * 测试处理过期问题
     */
    public function testProcessExpired(): JsonResponse
    {
        try {
            $processedCount = $this->questionService->processExpiredQuestions();

            return $this->success([
                'processed_count' => $processedCount,
                'message' => "已处理 {$processedCount} 个过期问题",
            ]);

        } catch (\Exception $e) {
            return $this->error('处理过期问题失败: ' . $e->getMessage());
        }
    }

    /**
     * 测试获取Agent的问题
     */
    public function testAgentQuestions(int $agentId): JsonResponse
    {
        try {
            $questions = $this->questionService->getAgentQuestions($agentId, [], 10);

            return $this->success([
                'agent_id' => $agentId,
                'questions' => $questions->items(),
                'total' => $questions->total(),
                'message' => 'Agent问题列表获取成功',
            ]);

        } catch (\Exception $e) {
            return $this->error('获取Agent问题失败: ' . $e->getMessage());
        }
    }

    /**
     * 创建多个测试问题
     */
    public function testCreateMultiple(): JsonResponse
    {
        try {
            $user = User::first();
            $agent = Agent::first();

            if (!$user || !$agent) {
                return $this->error('需要先创建用户和Agent');
            }

            $questions = [];
            $questionTypes = [AgentQuestion::TYPE_FEEDBACK];
            $priorities = [
                QuestionPriority::URGENT->value,
                QuestionPriority::HIGH->value,
                QuestionPriority::MEDIUM->value,
                QuestionPriority::LOW->value,
            ];

            for ($i = 1; $i <= 5; $i++) {
                $questionData = [
                    'agent_id' => $agent->id,
                    'user_id' => $user->id,
                    'title' => "批量测试问题 #{$i}",
                    'content' => "这是第 {$i} 个批量创建的测试问题。",
                    'question_type' => $questionTypes[0],
                    'priority' => $priorities[($i - 1) % 4],
                    'context' => [
                        'batch' => true,
                        'index' => $i,
                        'timestamp' => now()->toISOString(),
                    ],
                ];

                // 移除选项设置，因为只支持文本回答

                $question = $this->questionService->createQuestion($questionData);
                $questions[] = $question;
            }

            return $this->success([
                'questions' => $questions,
                'count' => count($questions),
                'message' => '批量创建问题成功',
            ]);

        } catch (\Exception $e) {
            return $this->error('批量创建问题失败: ' . $e->getMessage());
        }
    }

    /**
     * 获取问题详情
     */
    public function testShow(int $questionId): JsonResponse
    {
        try {
            $question = $this->questionService->getQuestionById($questionId);

            if (!$question) {
                return $this->notFound('问题不存在');
            }

            return $this->success([
                'question' => $question,
                'message' => '获取问题详情成功',
            ]);

        } catch (\Exception $e) {
            return $this->error('获取问题详情失败: ' . $e->getMessage());
        }
    }

    /**
     * 测试高优先级问题获取
     */
    public function testHighPriority(): JsonResponse
    {
        try {
            $questions = $this->questionService->getHighPriorityPendingQuestions(5);

            return $this->success([
                'questions' => $questions,
                'count' => $questions->count(),
                'message' => '获取高优先级问题成功',
            ]);

        } catch (\Exception $e) {
            return $this->error('获取高优先级问题失败: ' . $e->getMessage());
        }
    }

    /**
     * 测试即将过期问题获取
     */
    public function testExpiring(): JsonResponse
    {
        try {
            $questions = $this->questionService->getExpiringQuestions(60); // 1小时内过期

            return $this->success([
                'questions' => $questions,
                'count' => $questions->count(),
                'message' => '获取即将过期问题成功',
            ]);

        } catch (\Exception $e) {
            return $this->error('获取即将过期问题失败: ' . $e->getMessage());
        }
    }

    /**
     * 测试Agent问题统计
     */
    public function testAgentStats(int $agentId): JsonResponse
    {
        try {
            $stats = $this->questionService->getAgentQuestionStats($agentId);

            return $this->success([
                'agent_id' => $agentId,
                'stats' => $stats,
                'message' => 'Agent问题统计获取成功',
            ]);

        } catch (\Exception $e) {
            return $this->error('获取Agent统计失败: ' . $e->getMessage());
        }
    }

    /**
     * 测试通知功能
     */
    public function testNotification(int $questionId): JsonResponse
    {
        try {
            $question = $this->questionService->getQuestionById($questionId);
            if (!$question) {
                return $this->notFound('问题不存在');
            }

            // 测试新问题通知
            $success = $this->notificationService->notifyNewQuestion($question);

            return $this->success([
                'question_id' => $questionId,
                'notification_sent' => $success,
                'message' => '通知测试完成',
            ]);

        } catch (\Exception $e) {
            return $this->error('通知测试失败: ' . $e->getMessage());
        }
    }

    /**
     * 测试排序功能
     */
    public function testSorting(): JsonResponse
    {
        try {
            // 测试不同排序方式
            $sortTests = [
                ['sort_by' => 'priority', 'sort_order' => 'desc'],
                ['sort_by' => 'created_at', 'sort_order' => 'desc'],
                ['sort_by' => 'expires_at', 'sort_order' => 'asc'],
                ['sort_by' => 'status', 'sort_order' => 'asc'],
            ];

            $results = [];
            foreach ($sortTests as $sortConfig) {
                $questions = $this->questionService->getQuestions($sortConfig, 5);
                $results[] = [
                    'sort_config' => $sortConfig,
                    'count' => $questions->total(),
                    'first_question' => $questions->items()[0] ?? null,
                ];
            }

            return $this->success([
                'sort_tests' => $results,
                'message' => '排序测试完成',
            ]);

        } catch (\Exception $e) {
            return $this->error('排序测试失败: ' . $e->getMessage());
        }
    }

    /**
     * 测试批量操作
     */
    public function testBatchOperations(): JsonResponse
    {
        try {
            // 获取一些待回答的问题
            $pendingQuestions = AgentQuestion::pending()->limit(3)->pluck('id')->toArray();

            if (empty($pendingQuestions)) {
                return $this->error('没有待回答的问题可供测试');
            }

            // 测试批量更新状态
            $updated = $this->questionService->batchUpdateStatus($pendingQuestions, AgentQuestion::STATUS_IGNORED);

            // 再改回来
            $this->questionService->batchUpdateStatus($pendingQuestions, AgentQuestion::STATUS_PENDING);

            return $this->success([
                'question_ids' => $pendingQuestions,
                'updated_count' => $updated,
                'message' => '批量操作测试完成',
            ]);

        } catch (\Exception $e) {
            return $this->error('批量操作测试失败: ' . $e->getMessage());
        }
    }

    /**
     * 创建过期测试问题
     */
    public function testCreateExpiring(): JsonResponse
    {
        try {
            $user = User::first();
            $agent = Agent::first();

            if (!$user || !$agent) {
                return $this->error('需要先创建用户和Agent');
            }

            // 创建一个5分钟后过期的问题
            $questionData = [
                'agent_id' => $agent->id,
                'user_id' => $user->id,
                'title' => '即将过期的测试问题',
                'content' => '这个问题将在5分钟后过期，用于测试过期处理功能。',
                'question_type' => AgentQuestion::TYPE_FEEDBACK,
                'priority' => AgentQuestion::PRIORITY_HIGH,
                'expires_in' => 300, // 5分钟
            ];

            $question = $this->questionService->createQuestion($questionData);

            return $this->success([
                'question' => $question,
                'expires_at' => $question->expires_at->toISOString(),
                'message' => '即将过期的测试问题创建成功',
            ]);

        } catch (\Exception $e) {
            return $this->error('创建过期测试问题失败: ' . $e->getMessage());
        }
    }
}
