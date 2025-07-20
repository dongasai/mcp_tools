<?php

namespace Tests\Unit\Agent;

use Tests\TestCase;
use App\Modules\Agent\Services\QuestionService;
use App\Modules\Agent\Models\Agent;
use App\Modules\Agent\Models\AgentQuestion;
use App\Modules\User\Models\User;
use App\Modules\Task\Models\Task;
use App\Modules\Project\Models\Project;
use App\Modules\Core\Contracts\LogInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;

class QuestionServiceTest extends TestCase
{
    use RefreshDatabase;

    private QuestionService $questionService;
    private $mockLogger;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->mockLogger = Mockery::mock(LogInterface::class);
        $this->mockLogger->shouldReceive('info')->andReturn(true);
        $this->mockLogger->shouldReceive('warning')->andReturn(true);
        $this->mockLogger->shouldReceive('error')->andReturn(true);
        
        $this->questionService = new QuestionService($this->mockLogger);
    }

    public function test_create_question_success()
    {
        // 创建测试数据
        $user = User::factory()->create();
        $agent = Agent::factory()->create(['user_id' => $user->id]);
        
        $questionData = [
            'agent_id' => $agent->id,
            'user_id' => $user->id,
            'title' => '测试问题',
            'content' => '这是一个测试问题的内容',
            'question_type' => AgentQuestion::TYPE_CHOICE,
            'priority' => AgentQuestion::PRIORITY_HIGH,
            'context' => ['test' => 'context'],
            'answer_options' => ['选项1', '选项2', '选项3'],
            'expires_in' => 3600,
        ];

        // 执行测试
        $question = $this->questionService->createQuestion($questionData);

        // 验证结果
        $this->assertInstanceOf(AgentQuestion::class, $question);
        $this->assertEquals($questionData['title'], $question->title);
        $this->assertEquals($questionData['content'], $question->content);
        $this->assertEquals($questionData['question_type'], $question->question_type);
        $this->assertEquals($questionData['priority'], $question->priority);
        $this->assertEquals(AgentQuestion::STATUS_PENDING, $question->status);
        $this->assertNotNull($question->expires_at);
        $this->assertEquals($questionData['context'], $question->context);
        $this->assertEquals($questionData['answer_options'], $question->answer_options);
    }

    public function test_create_question_with_invalid_data()
    {
        $this->expectException(\InvalidArgumentException::class);
        
        // 缺少必需字段
        $questionData = [
            'title' => '测试问题',
            // 缺少其他必需字段
        ];

        $this->questionService->createQuestion($questionData);
    }

    public function test_answer_question_success()
    {
        // 创建测试数据
        $user = User::factory()->create();
        $agent = Agent::factory()->create(['user_id' => $user->id]);
        $question = AgentQuestion::factory()->create([
            'agent_id' => $agent->id,
            'user_id' => $user->id,
            'status' => AgentQuestion::STATUS_PENDING,
        ]);

        // 执行测试
        $success = $this->questionService->answerQuestion(
            $question->id,
            '这是回答内容',
            AgentQuestion::ANSWER_TYPE_TEXT,
            $user->id
        );

        // 验证结果
        $this->assertTrue($success);
        
        $question->refresh();
        $this->assertEquals(AgentQuestion::STATUS_ANSWERED, $question->status);
        $this->assertEquals('这是回答内容', $question->answer);
        $this->assertEquals(AgentQuestion::ANSWER_TYPE_TEXT, $question->answer_type);
        $this->assertEquals($user->id, $question->answered_by);
        $this->assertNotNull($question->answered_at);
    }

    public function test_ignore_question_success()
    {
        // 创建测试数据
        $user = User::factory()->create();
        $agent = Agent::factory()->create(['user_id' => $user->id]);
        $question = AgentQuestion::factory()->create([
            'agent_id' => $agent->id,
            'user_id' => $user->id,
            'status' => AgentQuestion::STATUS_PENDING,
        ]);

        // 执行测试
        $success = $this->questionService->ignoreQuestion($question->id);

        // 验证结果
        $this->assertTrue($success);
        
        $question->refresh();
        $this->assertEquals(AgentQuestion::STATUS_IGNORED, $question->status);
    }

    public function test_get_questions_with_filters()
    {
        // 创建测试数据
        $user = User::factory()->create();
        $agent = Agent::factory()->create(['user_id' => $user->id]);
        
        // 创建不同状态的问题
        AgentQuestion::factory()->create([
            'agent_id' => $agent->id,
            'user_id' => $user->id,
            'status' => AgentQuestion::STATUS_PENDING,
            'priority' => AgentQuestion::PRIORITY_HIGH,
        ]);
        
        AgentQuestion::factory()->create([
            'agent_id' => $agent->id,
            'user_id' => $user->id,
            'status' => AgentQuestion::STATUS_ANSWERED,
            'priority' => AgentQuestion::PRIORITY_LOW,
        ]);

        // 测试过滤条件
        $pendingQuestions = $this->questionService->getQuestions([
            'status' => AgentQuestion::STATUS_PENDING
        ]);
        
        $this->assertEquals(1, $pendingQuestions->total());
        
        $highPriorityQuestions = $this->questionService->getQuestions([
            'priority' => AgentQuestion::PRIORITY_HIGH
        ]);
        
        $this->assertEquals(1, $highPriorityQuestions->total());
    }

    public function test_process_expired_questions()
    {
        // 创建测试数据
        $user = User::factory()->create();
        $agent = Agent::factory()->create(['user_id' => $user->id]);
        
        // 创建已过期的问题
        $expiredQuestion = AgentQuestion::factory()->create([
            'agent_id' => $agent->id,
            'user_id' => $user->id,
            'status' => AgentQuestion::STATUS_PENDING,
            'expires_at' => now()->subHour(), // 1小时前过期
        ]);
        
        // 创建未过期的问题
        $validQuestion = AgentQuestion::factory()->create([
            'agent_id' => $agent->id,
            'user_id' => $user->id,
            'status' => AgentQuestion::STATUS_PENDING,
            'expires_at' => now()->addHour(), // 1小时后过期
        ]);

        // 执行测试
        $processedCount = $this->questionService->processExpiredQuestions();

        // 验证结果
        $this->assertEquals(1, $processedCount);
        
        $expiredQuestion->refresh();
        $validQuestion->refresh();
        
        $this->assertEquals(AgentQuestion::STATUS_IGNORED, $expiredQuestion->status);
        $this->assertEquals(AgentQuestion::STATUS_PENDING, $validQuestion->status);
    }

    public function test_get_question_stats()
    {
        // 创建测试数据
        $user = User::factory()->create();
        $agent = Agent::factory()->create(['user_id' => $user->id]);
        
        // 创建不同状态和类型的问题
        AgentQuestion::factory()->create([
            'agent_id' => $agent->id,
            'user_id' => $user->id,
            'status' => AgentQuestion::STATUS_PENDING,
            'question_type' => AgentQuestion::TYPE_CHOICE,
            'priority' => AgentQuestion::PRIORITY_HIGH,
        ]);
        
        AgentQuestion::factory()->create([
            'agent_id' => $agent->id,
            'user_id' => $user->id,
            'status' => AgentQuestion::STATUS_ANSWERED,
            'question_type' => AgentQuestion::TYPE_FEEDBACK,
            'priority' => AgentQuestion::PRIORITY_MEDIUM,
        ]);

        // 执行测试
        $stats = $this->questionService->getQuestionStats();

        // 验证结果
        $this->assertEquals(2, $stats['total']);
        $this->assertEquals(1, $stats['pending']);
        $this->assertEquals(1, $stats['answered']);
        $this->assertEquals(0, $stats['ignored']);
        $this->assertEquals(1, $stats['by_type'][AgentQuestion::TYPE_CHOICE]);
        $this->assertEquals(1, $stats['by_type'][AgentQuestion::TYPE_FEEDBACK]);
        $this->assertEquals(1, $stats['by_priority'][AgentQuestion::PRIORITY_HIGH]);
        $this->assertEquals(1, $stats['by_priority'][AgentQuestion::PRIORITY_MEDIUM]);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
