<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Modules\Task\Enums\TASKSTATUS;
use App\Modules\Task\Enums\TASKTYPE;
use App\Modules\Task\Enums\TASKPRIORITY;

class TaskEnumTest extends TestCase
{
    public function test_task_status_enum_values()
    {
        $this->assertEquals('pending', TASKSTATUS::PENDING->value);
        $this->assertEquals('in_progress', TASKSTATUS::IN_PROGRESS->value);
        $this->assertEquals('completed', TASKSTATUS::COMPLETED->value);
        $this->assertEquals('blocked', TASKSTATUS::BLOCKED->value);
        $this->assertEquals('cancelled', TASKSTATUS::CANCELLED->value);
        $this->assertEquals('on_hold', TASKSTATUS::ON_HOLD->value);
    }

    public function test_task_status_labels()
    {
        $this->assertEquals('Pending', TASKSTATUS::PENDING->label());
        $this->assertEquals('In Progress', TASKSTATUS::IN_PROGRESS->label());
        $this->assertEquals('Completed', TASKSTATUS::COMPLETED->label());
        $this->assertEquals('Blocked', TASKSTATUS::BLOCKED->label());
        $this->assertEquals('Cancelled', TASKSTATUS::CANCELLED->label());
        $this->assertEquals('On Hold', TASKSTATUS::ON_HOLD->label());
    }

    public function test_task_status_transitions()
    {
        // 测试从 PENDING 可以转换到的状态
        $this->assertTrue(TASKSTATUS::PENDING->canTransitionTo(TASKSTATUS::IN_PROGRESS));
        $this->assertTrue(TASKSTATUS::PENDING->canTransitionTo(TASKSTATUS::BLOCKED));
        $this->assertTrue(TASKSTATUS::PENDING->canTransitionTo(TASKSTATUS::CANCELLED));
        $this->assertTrue(TASKSTATUS::PENDING->canTransitionTo(TASKSTATUS::ON_HOLD));
        $this->assertFalse(TASKSTATUS::PENDING->canTransitionTo(TASKSTATUS::COMPLETED));

        // 测试已完成的任务不能再转换
        $this->assertFalse(TASKSTATUS::COMPLETED->canTransitionTo(TASKSTATUS::PENDING));
        $this->assertFalse(TASKSTATUS::COMPLETED->canTransitionTo(TASKSTATUS::IN_PROGRESS));
    }

    public function test_task_status_states()
    {
        $this->assertTrue(TASKSTATUS::PENDING->isActive());
        $this->assertTrue(TASKSTATUS::IN_PROGRESS->isActive());
        $this->assertFalse(TASKSTATUS::COMPLETED->isActive());
        $this->assertFalse(TASKSTATUS::CANCELLED->isActive());

        $this->assertTrue(TASKSTATUS::COMPLETED->isCompleted());
        $this->assertFalse(TASKSTATUS::PENDING->isCompleted());

        $this->assertTrue(TASKSTATUS::COMPLETED->isTerminated());
        $this->assertTrue(TASKSTATUS::CANCELLED->isTerminated());
        $this->assertFalse(TASKSTATUS::PENDING->isTerminated());
    }

    public function test_task_type_enum_values()
    {
        $this->assertEquals('main', TASKTYPE::MAIN->value);
        $this->assertEquals('sub', TASKTYPE::SUB->value);
        $this->assertEquals('milestone', TASKTYPE::MILESTONE->value);
        $this->assertEquals('bug', TASKTYPE::BUG->value);
        $this->assertEquals('feature', TASKTYPE::FEATURE->value);
        $this->assertEquals('improvement', TASKTYPE::IMPROVEMENT->value);
    }

    public function test_task_type_labels()
    {
        $this->assertEquals('Main Task', TASKTYPE::MAIN->label());
        $this->assertEquals('Sub Task', TASKTYPE::SUB->label());
        $this->assertEquals('Milestone', TASKTYPE::MILESTONE->label());
        $this->assertEquals('Bug Fix', TASKTYPE::BUG->label());
        $this->assertEquals('Feature', TASKTYPE::FEATURE->label());
        $this->assertEquals('Improvement', TASKTYPE::IMPROVEMENT->label());
    }

    public function test_task_type_capabilities()
    {
        $this->assertTrue(TASKTYPE::MAIN->isMainTask());
        $this->assertFalse(TASKTYPE::SUB->isMainTask());

        $this->assertTrue(TASKTYPE::SUB->isSubTask());
        $this->assertFalse(TASKTYPE::MAIN->isSubTask());

        $this->assertTrue(TASKTYPE::MAIN->canHaveSubTasks());
        $this->assertTrue(TASKTYPE::MILESTONE->canHaveSubTasks());
        $this->assertTrue(TASKTYPE::FEATURE->canHaveSubTasks());
        $this->assertFalse(TASKTYPE::SUB->canHaveSubTasks());
    }

    public function test_task_priority_enum_values()
    {
        $this->assertEquals('low', TASKPRIORITY::LOW->value);
        $this->assertEquals('medium', TASKPRIORITY::MEDIUM->value);
        $this->assertEquals('high', TASKPRIORITY::HIGH->value);
        $this->assertEquals('urgent', TASKPRIORITY::URGENT->value);
    }

    public function test_task_priority_labels()
    {
        $this->assertEquals('Low', TASKPRIORITY::LOW->label());
        $this->assertEquals('Medium', TASKPRIORITY::MEDIUM->label());
        $this->assertEquals('High', TASKPRIORITY::HIGH->label());
        $this->assertEquals('Urgent', TASKPRIORITY::URGENT->label());
    }

    public function test_task_priority_values()
    {
        $this->assertEquals(1, TASKPRIORITY::LOW->value());
        $this->assertEquals(2, TASKPRIORITY::MEDIUM->value());
        $this->assertEquals(3, TASKPRIORITY::HIGH->value());
        $this->assertEquals(4, TASKPRIORITY::URGENT->value());
    }

    public function test_task_priority_comparisons()
    {
        $this->assertTrue(TASKPRIORITY::HIGH->isHigh());
        $this->assertTrue(TASKPRIORITY::URGENT->isHigh());
        $this->assertFalse(TASKPRIORITY::LOW->isHigh());

        $this->assertTrue(TASKPRIORITY::URGENT->isUrgent());
        $this->assertFalse(TASKPRIORITY::HIGH->isUrgent());

        $this->assertTrue(TASKPRIORITY::HIGH->isHigherThan(TASKPRIORITY::MEDIUM));
        $this->assertFalse(TASKPRIORITY::LOW->isHigherThan(TASKPRIORITY::MEDIUM));

        $this->assertTrue(TASKPRIORITY::LOW->isLowerThan(TASKPRIORITY::HIGH));
        $this->assertFalse(TASKPRIORITY::HIGH->isLowerThan(TASKPRIORITY::LOW));
    }

    public function test_enum_select_options()
    {
        $statusOptions = TASKSTATUS::selectOptions();
        $this->assertIsArray($statusOptions);
        $this->assertArrayHasKey('pending', $statusOptions);
        $this->assertEquals('Pending', $statusOptions['pending']);

        $typeOptions = TASKTYPE::selectOptions();
        $this->assertIsArray($typeOptions);
        $this->assertArrayHasKey('main', $typeOptions);
        $this->assertEquals('Main Task', $typeOptions['main']);

        $priorityOptions = TASKPRIORITY::selectOptions();
        $this->assertIsArray($priorityOptions);
        $this->assertArrayHasKey('low', $priorityOptions);
        $this->assertEquals('Low', $priorityOptions['low']);
    }
}
