<?php

namespace Modules\MCP\Controllers;

use Modules\Agent\Services\QuestionService;
use Modules\Agent\Services\QuestionAnalyticsService;
use Modules\Agent\Models\AgentQuestion;
use App\Modules\Core\Controllers\BaseController;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

/**
 * Agent问题功能Phase 3测试控制器
 * 
 * 测试高级功能：批量处理、搜索、分析、上下文提取
 */
class QuestionPhase3TestController extends BaseController
{
    public function __construct(
        private QuestionService $questionService,
        private QuestionAnalyticsService $analyticsService
    ) {}

    /**
     * 测试批量状态更新
     */
    public function testBatchUpdateStatus(): JsonResponse
    {
        try {
            // 创建测试问题
            $questions = [];
            for ($i = 1; $i <= 5; $i++) {
                $questions[] = $this->questionService->createQuestion([
                    'agent_id' => 1,
                    'user_id' => 1,
                    'title' => "批量测试问题 {$i}",
                    'content' => "这是批量测试问题 {$i} 的详细内容",
                    'question_type' => 'CHOICE',
                    'priority' => 'MEDIUM',
                    'options' => ['选项A', '选项B'],
                ]);
            }

            $questionIds = array_map(fn($q) => $q->id, $questions);

            // 测试批量更新为已回答状态
            $results = $this->questionService->batchUpdateStatus(
                $questionIds,
                AgentQuestion::STATUS_ANSWERED,
                '批量回答测试'
            );

            $successCount = count(array_filter($results, fn($r) => $r['status'] === 'success'));

            return $this->success([
                'message' => '批量状态更新测试完成',
                'created_questions' => count($questions),
                'update_results' => $results,
                'success_count' => $successCount,
                'test_passed' => $successCount === count($questions),
            ]);

        } catch (\Exception $e) {
            return $this->error('批量状态更新测试失败: ' . $e->getMessage(), 500);
        }
    }

    /**
     * 测试批量删除
     */
    public function testBatchDelete(): JsonResponse
    {
        try {
            // 创建测试问题
            $questions = [];
            for ($i = 1; $i <= 3; $i++) {
                $questions[] = $this->questionService->createQuestion([
                    'agent_id' => 1,
                    'user_id' => 1,
                    'title' => "批量删除测试问题 {$i}",
                    'content' => "这是批量删除测试问题 {$i} 的详细内容",
                    'question_type' => 'FEEDBACK',
                    'priority' => 'LOW',
                ]);
            }

            $questionIds = array_map(fn($q) => $q->id, $questions);

            // 测试批量删除
            $results = $this->questionService->batchDelete($questionIds);

            $successCount = count(array_filter($results, fn($r) => $r['status'] === 'success'));

            // 验证删除结果
            $remainingCount = AgentQuestion::whereIn('id', $questionIds)->count();

            return $this->success([
                'message' => '批量删除测试完成',
                'created_questions' => count($questions),
                'delete_results' => $results,
                'success_count' => $successCount,
                'remaining_count' => $remainingCount,
                'test_passed' => $successCount === count($questions) && $remainingCount === 0,
            ]);

        } catch (\Exception $e) {
            return $this->error('批量删除测试失败: ' . $e->getMessage(), 500);
        }
    }

    /**
     * 测试搜索功能
     */
    public function testSearch(): JsonResponse
    {
        try {
            // 创建不同类型的测试问题
            $testQuestions = [
                [
                    'title' => '搜索测试：Laravel框架问题',
                    'content' => 'Laravel框架相关的技术问题',
                    'question_type' => 'CHOICE',
                    'priority' => 'HIGH',
                    'context' => 'Laravel开发相关',
                ],
                [
                    'title' => '搜索测试：数据库优化问题',
                    'content' => '数据库性能优化相关问题',
                    'question_type' => 'FEEDBACK',
                    'priority' => 'MEDIUM',
                    'context' => 'MySQL性能优化',
                ],
                [
                    'title' => '搜索测试：前端界面问题',
                    'content' => '前端界面开发相关问题',
                    'question_type' => 'CHOICE',
                    'priority' => 'LOW',
                    'context' => 'Vue.js界面开发',
                ],
            ];

            $createdQuestions = [];
            foreach ($testQuestions as $data) {
                $createdQuestions[] = $this->questionService->createQuestion([
                    'agent_id' => 1,
                    'user_id' => 1,
                    ...$data,
                ]);
            }

            // 测试不同的搜索条件
            $searchTests = [
                [
                    'name' => '关键词搜索',
                    'query' => 'Laravel',
                    'filters' => [],
                ],
                [
                    'name' => '类型过滤',
                    'query' => '',
                    'filters' => ['question_type' => 'CHOICE'],
                ],
                [
                    'name' => '优先级过滤',
                    'query' => '',
                    'filters' => ['priority' => 'HIGH'],
                ],
                [
                    'name' => '组合搜索',
                    'query' => '测试',
                    'filters' => ['question_type' => 'FEEDBACK', 'priority' => 'MEDIUM'],
                ],
            ];

            $searchResults = [];
            foreach ($searchTests as $test) {
                $results = $this->questionService->searchQuestions(
                    $test['query'],
                    $test['filters'],
                    10
                );

                $searchResults[] = [
                    'test_name' => $test['name'],
                    'query' => $test['query'],
                    'filters' => $test['filters'],
                    'total_results' => $results->total(),
                    'results_count' => count($results->items()),
                ];
            }

            return $this->success([
                'message' => '搜索功能测试完成',
                'created_questions' => count($createdQuestions),
                'search_tests' => $searchResults,
                'test_passed' => count($searchResults) === count($searchTests),
            ]);

        } catch (\Exception $e) {
            return $this->error('搜索功能测试失败: ' . $e->getMessage(), 500);
        }
    }

    /**
     * 测试分析功能
     */
    public function testAnalytics(): JsonResponse
    {
        try {
            // 创建多样化的测试数据
            $testData = [
                ['question_type' => 'CHOICE', 'priority' => 'URGENT', 'status' => 'ANSWERED'],
                ['question_type' => 'CHOICE', 'priority' => 'HIGH', 'status' => 'PENDING'],
                ['question_type' => 'FEEDBACK', 'priority' => 'MEDIUM', 'status' => 'ANSWERED'],
                ['question_type' => 'FEEDBACK', 'priority' => 'LOW', 'status' => 'IGNORED'],
                ['question_type' => 'CHOICE', 'priority' => 'HIGH', 'status' => 'ANSWERED'],
            ];

            $createdQuestions = [];
            foreach ($testData as $data) {
                $question = $this->questionService->createQuestion([
                    'agent_id' => 1,
                    'user_id' => 1,
                    'title' => '分析测试问题：' . $data['question_type'] . ' - ' . $data['priority'],
                    'content' => '这是分析测试问题的详细内容',
                    ...$data,
                ]);

                // 如果是已回答状态，设置回答
                if ($data['status'] === 'ANSWERED') {
                    $this->questionService->answerQuestion($question->id, '测试回答');
                } elseif ($data['status'] === 'IGNORED') {
                    $this->questionService->ignoreQuestion($question->id);
                }

                $createdQuestions[] = $question;
            }

            // 测试各种分析功能
            $analytics = [
                'type_stats' => $this->analyticsService->getQuestionTypeStats(),
                'status_stats' => $this->analyticsService->getQuestionStatusStats(),
                'priority_stats' => $this->analyticsService->getQuestionPriorityStats(),
                'response_time' => $this->analyticsService->getResponseTimeAnalysis(),
                'agent_patterns' => $this->analyticsService->getAgentQuestionPatterns()->take(5),
                'trends' => $this->analyticsService->getQuestionTrends(7),
            ];

            return $this->success([
                'message' => '分析功能测试完成',
                'created_questions' => count($createdQuestions),
                'analytics' => $analytics,
                'test_passed' => !empty($analytics['type_stats']) && !empty($analytics['status_stats']),
            ]);

        } catch (\Exception $e) {
            return $this->error('分析功能测试失败: ' . $e->getMessage(), 500);
        }
    }

    /**
     * 测试上下文提取
     */
    public function testContextExtraction(): JsonResponse
    {
        try {
            // 创建一个问题用于测试上下文提取
            $question = $this->questionService->createQuestion([
                'agent_id' => 1,
                'user_id' => 1,
                'title' => '上下文提取测试问题：如何优化数据库查询性能？',
                'content' => '数据库查询性能优化的详细问题描述',
                'question_type' => 'FEEDBACK',
                'priority' => 'HIGH',
                'context' => 'MySQL数据库优化，涉及索引设计和查询优化',
            ]);

            // 创建一些相关问题
            for ($i = 1; $i <= 3; $i++) {
                $this->questionService->createQuestion([
                    'agent_id' => 1,
                    'user_id' => 1,
                    'title' => "相关问题 {$i}：数据库相关",
                    'content' => "相关问题 {$i} 的详细内容",
                    'question_type' => 'CHOICE',
                    'priority' => 'MEDIUM',
                ]);
            }

            // 提取上下文
            $context = $this->questionService->extractContext($question);

            // 验证上下文内容
            $contextKeys = [
                'question_id',
                'agent_info',
                'related_questions',
                'patterns',
                'timing',
            ];

            $hasAllKeys = true;
            foreach ($contextKeys as $key) {
                if (!isset($context[$key])) {
                    $hasAllKeys = false;
                    break;
                }
            }

            return $this->success([
                'message' => '上下文提取测试完成',
                'question_id' => $question->id,
                'context' => $context,
                'context_keys' => array_keys($context),
                'expected_keys' => $contextKeys,
                'has_all_keys' => $hasAllKeys,
                'related_questions_count' => count($context['related_questions'] ?? []),
                'test_passed' => $hasAllKeys && !empty($context['agent_info']),
            ]);

        } catch (\Exception $e) {
            return $this->error('上下文提取测试失败: ' . $e->getMessage(), 500);
        }
    }

    /**
     * 运行所有Phase 3测试
     */
    public function runAllTests(): JsonResponse
    {
        try {
            $tests = [
                'batch_update_status' => $this->testBatchUpdateStatus(),
                'batch_delete' => $this->testBatchDelete(),
                'search' => $this->testSearch(),
                'analytics' => $this->testAnalytics(),
                'context_extraction' => $this->testContextExtraction(),
            ];

            $results = [];
            $allPassed = true;

            foreach ($tests as $testName => $response) {
                $data = json_decode($response->getContent(), true);
                $passed = $data['data']['test_passed'] ?? false;
                
                $results[$testName] = [
                    'passed' => $passed,
                    'message' => $data['data']['message'] ?? '',
                ];

                if (!$passed) {
                    $allPassed = false;
                }
            }

            return $this->success([
                'message' => 'Agent问题功能Phase 3全面测试完成',
                'overall_result' => $allPassed ? 'PASSED' : 'FAILED',
                'test_results' => $results,
                'passed_count' => count(array_filter($results, fn($r) => $r['passed'])),
                'total_count' => count($results),
                'completion_time' => now()->toISOString(),
            ]);

        } catch (\Exception $e) {
            return $this->error('Phase 3测试运行失败: ' . $e->getMessage(), 500);
        }
    }
}
