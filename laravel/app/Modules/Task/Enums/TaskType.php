<?php

namespace App\Modules\Task\Enums;

enum TaskType: string
{
    case MAIN = 'main';
    case SUB = 'sub';
    case MILESTONE = 'milestone';
    case BUG = 'bug';
    case FEATURE = 'feature';
    case IMPROVEMENT = 'improvement';

    /**
     * 获取类型的显示名称
     */
    public function label(): string
    {
        return match($this) {
            self::MAIN => 'Main Task',
            self::SUB => 'Sub Task',
            self::MILESTONE => 'Milestone',
            self::BUG => 'Bug Fix',
            self::FEATURE => 'Feature',
            self::IMPROVEMENT => 'Improvement',
        };
    }

    /**
     * 获取类型的颜色
     */
    public function color(): string
    {
        return match($this) {
            self::MAIN => 'primary',
            self::SUB => 'secondary',
            self::MILESTONE => 'warning',
            self::BUG => 'danger',
            self::FEATURE => 'success',
            self::IMPROVEMENT => 'info',
        };
    }

    /**
     * 获取类型的图标
     */
    public function icon(): string
    {
        return match($this) {
            self::MAIN => 'fa-tasks',
            self::SUB => 'fa-list',
            self::MILESTONE => 'fa-flag',
            self::BUG => 'fa-bug',
            self::FEATURE => 'fa-plus',
            self::IMPROVEMENT => 'fa-arrow-up',
        };
    }

    /**
     * 获取所有类型选项
     */
    public static function options(): array
    {
        return array_map(
            fn(TaskType $type) => [
                'value' => $type->value,
                'label' => $type->label(),
                'color' => $type->color(),
                'icon' => $type->icon(),
            ],
            self::cases()
        );
    }

    /**
     * 获取类型选项用于表单选择
     */
    public static function selectOptions(): array
    {
        return array_combine(
            array_map(fn(TaskType $type) => $type->value, self::cases()),
            array_map(fn(TaskType $type) => $type->label(), self::cases())
        );
    }

    /**
     * 检查是否为主任务
     */
    public function isMainTask(): bool
    {
        return $this === self::MAIN;
    }

    /**
     * 检查是否为子任务
     */
    public function isSubTask(): bool
    {
        return $this === self::SUB;
    }

    /**
     * 检查是否可以有子任务
     */
    public function canHaveSubTasks(): bool
    {
        return in_array($this, [self::MAIN, self::MILESTONE, self::FEATURE]);
    }
}
