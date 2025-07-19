<?php

namespace App\Modules\Task\Helpers;

use App\Modules\Task\Enums\TaskStatus;
use App\Modules\Task\Enums\TaskType;
use App\Modules\Task\Enums\TaskPriority;

class TaskValidationHelper
{
    /**
     * 获取任务状态的验证规则
     */
    public static function getStatusValidationRule(): string
    {
        $values = array_map(fn(TaskStatus $status) => $status->value, TaskStatus::cases());
        return 'string|in:' . implode(',', $values);
    }

    /**
     * 获取任务类型的验证规则
     */
    public static function getTypeValidationRule(): string
    {
        $values = array_map(fn(TaskType $type) => $type->value, TaskType::cases());
        return 'string|in:' . implode(',', $values);
    }

    /**
     * 获取任务优先级的验证规则
     */
    public static function getPriorityValidationRule(): string
    {
        $values = array_map(fn(TaskPriority $priority) => $priority->value, TaskPriority::cases());
        return 'string|in:' . implode(',', $values);
    }

    /**
     * 获取创建任务的验证规则
     */
    public static function getCreateTaskRules(): array
    {
        return [
            'title' => 'required|string|min:2|max:255',
            'description' => 'string|max:2000',
            'type' => self::getTypeValidationRule(),
            'priority' => self::getPriorityValidationRule(),
            'project_id' => 'integer',
            'agent_id' => 'integer',
            'parent_task_id' => 'integer',
            'assigned_to' => 'string|max:255',
            'due_date' => 'date',
            'estimated_hours' => 'numeric|min:0',
            'tags' => 'array',
        ];
    }

    /**
     * 获取更新任务的验证规则
     */
    public static function getUpdateTaskRules(): array
    {
        return [
            'title' => 'string|min:2|max:255',
            'description' => 'string|max:2000',
            'type' => self::getTypeValidationRule(),
            'priority' => self::getPriorityValidationRule(),
            'status' => self::getStatusValidationRule(),
            'agent_id' => 'integer',
            'assigned_to' => 'string|max:255',
            'due_date' => 'date',
            'estimated_hours' => 'numeric|min:0',
            'actual_hours' => 'numeric|min:0',
            'progress' => 'integer|min:0|max:100',
            'tags' => 'array',
            'result' => 'array',
        ];
    }

    /**
     * 获取状态转换的验证规则
     */
    public static function getStatusTransitionRules(TaskStatus $currentStatus): array
    {
        $availableStatuses = $currentStatus->getAvailableTransitions();
        $values = array_map(fn(TaskStatus $status) => $status->value, $availableStatuses);
        
        return [
            'status' => 'required|string|in:' . implode(',', $values),
        ];
    }

    /**
     * 验证状态转换是否有效
     */
    public static function validateStatusTransition(TaskStatus $currentStatus, TaskStatus $newStatus): bool
    {
        return $currentStatus->canTransitionTo($newStatus);
    }

    /**
     * 获取任务类型是否可以有子任务的验证
     */
    public static function validateCanHaveSubTasks(TaskType $type): bool
    {
        return $type->canHaveSubTasks();
    }
}
