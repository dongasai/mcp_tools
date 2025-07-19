<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Modules\Task\Enums\TaskStatus;
use App\Modules\Task\Enums\TaskType;
use App\Modules\Task\Enums\TaskPriority;

class TaskEnumTest extends TestCase
{
    public function test_task_status_enum_values()
    {
        $this->assertEquals('pending', TaskStatus::PENDING->value);
        $this->assertEquals('in_progress', TaskStatus::IN_PROGRESS->value);
        $this->assertEquals('completed', TaskStatus::COMPLETED->value);
        $this->assertEquals('blocked', TaskStatus::BLOCKED->value);
        $this->assertEquals('cancelled', TaskStatus::CANCELLED->value);
        $this->assertEquals('on_hold', TaskStatus::ON_HOLD->value);
    }

    public function test_task_status_labels()
    {
        $this->assertEquals('Pending', TaskStatus::PENDING->label());
        $this->assertEquals('In Progress', TaskStatus::IN_PROGRESS->label());
        $this->assertEquals('Completed', TaskStatus::COMPLETED->label());
        $this->assertEquals('Blocked', TaskStatus::BLOCKED->label());
        $this->assertEquals('Cancelled', TaskStatus::CANCELLED->label());
        $this->assertEquals('On Hold', TaskStatus::ON_HOLD->label());
    }

    public function test_task_status_transitions()
    {
        // 测试从 PENDING 可以转换到的状态
        $this->assertTrue(TaskStatus::PENDING->canTransitionTo(TaskStatus::IN_PROGRESS));
        $this->assertTrue(TaskStatus::PENDING->canTransitionTo(TaskStatus::BLOCKED));
        $this->assertTrue(TaskStatus::PENDING->canTransitionTo(TaskStatus::CANCELLED));
        $this->assertTrue(TaskStatus::PENDING->canTransitionTo(TaskStatus::ON_HOLD));
        $this->assertFalse(TaskStatus::PENDING->canTransitionTo(TaskStatus::COMPLETED));

        // 测试已完成的任务不能再转换
        $this->assertFalse(TaskStatus::COMPLETED->canTransitionTo(TaskStatus::PENDING));
        $this->assertFalse(TaskStatus::COMPLETED->canTransitionTo(TaskStatus::IN_PROGRESS));
    }

    public function test_task_status_states()
    {
        $this->assertTrue(TaskStatus::PENDING->isActive());
        $this->assertTrue(TaskStatus::IN_PROGRESS->isActive());
        $this->assertFalse(TaskStatus::COMPLETED->isActive());
        $this->assertFalse(TaskStatus::CANCELLED->isActive());

        $this->assertTrue(TaskStatus::COMPLETED->isCompleted());
        $this->assertFalse(TaskStatus::PENDING->isCompleted());

        $this->assertTrue(TaskStatus::COMPLETED->isTerminated());
        $this->assertTrue(TaskStatus::CANCELLED->isTerminated());
        $this->assertFalse(TaskStatus::PENDING->isTerminated());
    }

    public function test_task_type_enum_values()
    {
        $this->assertEquals('main', TaskType::MAIN->value);
        $this->assertEquals('sub', TaskType::SUB->value);
        $this->assertEquals('milestone', TaskType::MILESTONE->value);
        $this->assertEquals('bug', TaskType::BUG->value);
        $this->assertEquals('feature', TaskType::FEATURE->value);
        $this->assertEquals('improvement', TaskType::IMPROVEMENT->value);
    }

    public function test_task_type_labels()
    {
        $this->assertEquals('Main Task', TaskType::MAIN->label());
        $this->assertEquals('Sub Task', TaskType::SUB->label());
        $this->assertEquals('Milestone', TaskType::MILESTONE->label());
        $this->assertEquals('Bug Fix', TaskType::BUG->label());
        $this->assertEquals('Feature', TaskType::FEATURE->label());
        $this->assertEquals('Improvement', TaskType::IMPROVEMENT->label());
    }

    public function test_task_type_capabilities()
    {
        $this->assertTrue(TaskType::MAIN->isMainTask());
        $this->assertFalse(TaskType::SUB->isMainTask());

        $this->assertTrue(TaskType::SUB->isSubTask());
        $this->assertFalse(TaskType::MAIN->isSubTask());

        $this->assertTrue(TaskType::MAIN->canHaveSubTasks());
        $this->assertTrue(TaskType::MILESTONE->canHaveSubTasks());
        $this->assertTrue(TaskType::FEATURE->canHaveSubTasks());
        $this->assertFalse(TaskType::SUB->canHaveSubTasks());
    }

    public function test_task_priority_enum_values()
    {
        $this->assertEquals('low', TaskPriority::LOW->value);
        $this->assertEquals('medium', TaskPriority::MEDIUM->value);
        $this->assertEquals('high', TaskPriority::HIGH->value);
        $this->assertEquals('urgent', TaskPriority::URGENT->value);
    }

    public function test_task_priority_labels()
    {
        $this->assertEquals('Low', TaskPriority::LOW->label());
        $this->assertEquals('Medium', TaskPriority::MEDIUM->label());
        $this->assertEquals('High', TaskPriority::HIGH->label());
        $this->assertEquals('Urgent', TaskPriority::URGENT->label());
    }

    public function test_task_priority_values()
    {
        $this->assertEquals(1, TaskPriority::LOW->value());
        $this->assertEquals(2, TaskPriority::MEDIUM->value());
        $this->assertEquals(3, TaskPriority::HIGH->value());
        $this->assertEquals(4, TaskPriority::URGENT->value());
    }

    public function test_task_priority_comparisons()
    {
        $this->assertTrue(TaskPriority::HIGH->isHigh());
        $this->assertTrue(TaskPriority::URGENT->isHigh());
        $this->assertFalse(TaskPriority::LOW->isHigh());

        $this->assertTrue(TaskPriority::URGENT->isUrgent());
        $this->assertFalse(TaskPriority::HIGH->isUrgent());

        $this->assertTrue(TaskPriority::HIGH->isHigherThan(TaskPriority::MEDIUM));
        $this->assertFalse(TaskPriority::LOW->isHigherThan(TaskPriority::MEDIUM));

        $this->assertTrue(TaskPriority::LOW->isLowerThan(TaskPriority::HIGH));
        $this->assertFalse(TaskPriority::HIGH->isLowerThan(TaskPriority::LOW));
    }

    public function test_enum_select_options()
    {
        $statusOptions = TaskStatus::selectOptions();
        $this->assertIsArray($statusOptions);
        $this->assertArrayHasKey('pending', $statusOptions);
        $this->assertEquals('Pending', $statusOptions['pending']);

        $typeOptions = TaskType::selectOptions();
        $this->assertIsArray($typeOptions);
        $this->assertArrayHasKey('main', $typeOptions);
        $this->assertEquals('Main Task', $typeOptions['main']);

        $priorityOptions = TaskPriority::selectOptions();
        $this->assertIsArray($priorityOptions);
        $this->assertArrayHasKey('low', $priorityOptions);
        $this->assertEquals('Low', $priorityOptions['low']);
    }
}
