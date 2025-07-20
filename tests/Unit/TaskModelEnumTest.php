<?php

namespace Tests\Unit;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Modules\Task\Models\Task;
use App\Modules\Task\Enums\TASKSTATUS;
use App\Modules\Task\Enums\TASKTYPE;
use App\Modules\Task\Enums\TASKPRIORITY;
use App\Models\User;

class TaskModelEnumTest extends TestCase
{
    use RefreshDatabase;

    public function test_task_model_uses_enums()
    {
        $user = User::factory()->create();
        
        $task = Task::create([
            'user_id' => $user->id,
            'title' => 'Test Task',
            'description' => 'Test Description',
            'type' => TASKTYPE::MAIN->value,
            'status' => TASKSTATUS::PENDING->value,
            'priority' => TASKPRIORITY::HIGH->value,
        ]);

        // 验证枚举类型转换
        $this->assertInstanceOf(TASKSTATUS::class, $task->status);
        $this->assertInstanceOf(TASKTYPE::class, $task->type);
        $this->assertInstanceOf(TASKPRIORITY::class, $task->priority);

        // 验证枚举值
        $this->assertEquals(TASKSTATUS::PENDING, $task->status);
        $this->assertEquals(TASKTYPE::MAIN, $task->type);
        $this->assertEquals(TASKPRIORITY::HIGH, $task->priority);
    }

    public function test_task_model_enum_methods()
    {
        $user = User::factory()->create();
        
        $task = Task::create([
            'user_id' => $user->id,
            'title' => 'Test Task',
            'type' => TASKTYPE::MAIN->value,
            'status' => TASKSTATUS::PENDING->value,
            'priority' => TASKPRIORITY::MEDIUM->value,
        ]);

        // 测试状态检查方法
        $this->assertFalse($task->isCompleted());
        $this->assertFalse($task->isInProgress());
        $this->assertFalse($task->isBlocked());

        // 测试类型检查方法
        $this->assertTrue($task->isMainTask());
        $this->assertFalse($task->isSubTask());
    }

    public function test_task_status_transitions()
    {
        $user = User::factory()->create();
        
        $task = Task::create([
            'user_id' => $user->id,
            'title' => 'Test Task',
            'status' => TASKSTATUS::PENDING->value,
        ]);

        // 开始任务
        $task->start();
        $task->refresh();
        $this->assertEquals(TASKSTATUS::IN_PROGRESS, $task->status);
        $this->assertTrue($task->isInProgress());

        // 完成任务
        $task->complete();
        $task->refresh();
        $this->assertEquals(TASKSTATUS::COMPLETED, $task->status);
        $this->assertTrue($task->isCompleted());
        $this->assertEquals(100, $task->progress);

        // 阻塞任务（从新任务开始）
        $blockedTask = Task::create([
            'user_id' => $user->id,
            'title' => 'Blocked Task',
            'status' => TASKSTATUS::PENDING->value,
        ]);
        
        $blockedTask->block();
        $blockedTask->refresh();
        $this->assertEquals(TASKSTATUS::BLOCKED, $blockedTask->status);
        $this->assertTrue($blockedTask->isBlocked());

        // 取消任务
        $cancelledTask = Task::create([
            'user_id' => $user->id,
            'title' => 'Cancelled Task',
            'status' => TASKSTATUS::PENDING->value,
        ]);
        
        $cancelledTask->cancel();
        $cancelledTask->refresh();
        $this->assertEquals(TASKSTATUS::CANCELLED, $cancelledTask->status);
    }

    public function test_task_static_methods_return_enum_options()
    {
        $statuses = Task::getStatuses();
        $this->assertIsArray($statuses);
        $this->assertArrayHasKey('pending', $statuses);
        $this->assertEquals('Pending', $statuses['pending']);

        $types = Task::getTypes();
        $this->assertIsArray($types);
        $this->assertArrayHasKey('main', $types);
        $this->assertEquals('Main Task', $types['main']);

        $priorities = Task::getPriorities();
        $this->assertIsArray($priorities);
        $this->assertArrayHasKey('low', $priorities);
        $this->assertEquals('Low', $priorities['low']);
    }

    public function test_task_scopes_work_with_enums()
    {
        $user = User::factory()->create();
        
        // 创建不同状态的任务
        $pendingTask = Task::create([
            'user_id' => $user->id,
            'title' => 'Pending Task',
            'status' => TASKSTATUS::PENDING->value,
        ]);

        $inProgressTask = Task::create([
            'user_id' => $user->id,
            'title' => 'In Progress Task',
            'status' => TASKSTATUS::IN_PROGRESS->value,
        ]);

        $completedTask = Task::create([
            'user_id' => $user->id,
            'title' => 'Completed Task',
            'status' => TASKSTATUS::COMPLETED->value,
        ]);

        // 测试状态查询作用域
        $pendingTasks = Task::byStatus(TASKSTATUS::PENDING)->get();
        $this->assertCount(1, $pendingTasks);
        $this->assertEquals($pendingTask->id, $pendingTasks->first()->id);

        $inProgressTasks = Task::byStatus(TASKSTATUS::IN_PROGRESS)->get();
        $this->assertCount(1, $inProgressTasks);
        $this->assertEquals($inProgressTask->id, $inProgressTasks->first()->id);

        $completedTasks = Task::byStatus(TASKSTATUS::COMPLETED)->get();
        $this->assertCount(1, $completedTasks);
        $this->assertEquals($completedTask->id, $completedTasks->first()->id);
    }

    public function test_task_progress_auto_completion()
    {
        $user = User::factory()->create();
        
        $task = Task::create([
            'user_id' => $user->id,
            'title' => 'Progress Task',
            'status' => TASKSTATUS::IN_PROGRESS->value,
            'progress' => 50,
        ]);

        // 更新进度到100%应该自动完成任务
        $task->updateProgress(100);
        $task->refresh();
        
        $this->assertEquals(100, $task->progress);
        $this->assertEquals(TASKSTATUS::COMPLETED, $task->status);
        $this->assertTrue($task->isCompleted());
    }
}
