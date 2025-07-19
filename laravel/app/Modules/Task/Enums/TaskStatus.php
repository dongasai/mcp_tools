<?php

namespace App\Modules\Task\Enums;

enum TaskStatus: string
{
    case PENDING = 'pending';
    case IN_PROGRESS = 'in_progress';
    case COMPLETED = 'completed';
    case BLOCKED = 'blocked';
    case CANCELLED = 'cancelled';
    case ON_HOLD = 'on_hold';

    /**
     * 获取状态的显示名称
     */
    public function label(): string
    {
        return match($this) {
            self::PENDING => 'Pending',
            self::IN_PROGRESS => 'In Progress',
            self::COMPLETED => 'Completed',
            self::BLOCKED => 'Blocked',
            self::CANCELLED => 'Cancelled',
            self::ON_HOLD => 'On Hold',
        };
    }

    /**
     * 获取状态的颜色
     */
    public function color(): string
    {
        return match($this) {
            self::PENDING => 'warning',
            self::IN_PROGRESS => 'primary',
            self::COMPLETED => 'success',
            self::BLOCKED => 'danger',
            self::CANCELLED => 'secondary',
            self::ON_HOLD => 'info',
        };
    }

    /**
     * 获取状态的图标
     */
    public function icon(): string
    {
        return match($this) {
            self::PENDING => 'fa-clock',
            self::IN_PROGRESS => 'fa-play',
            self::COMPLETED => 'fa-check',
            self::BLOCKED => 'fa-ban',
            self::CANCELLED => 'fa-times',
            self::ON_HOLD => 'fa-pause',
        };
    }

    /**
     * 获取所有状态选项
     */
    public static function options(): array
    {
        return array_map(
            fn(TaskStatus $status) => [
                'value' => $status->value,
                'label' => $status->label(),
                'color' => $status->color(),
                'icon' => $status->icon(),
            ],
            self::cases()
        );
    }

    /**
     * 获取状态选项用于表单选择
     */
    public static function selectOptions(): array
    {
        return array_combine(
            array_map(fn(TaskStatus $status) => $status->value, self::cases()),
            array_map(fn(TaskStatus $status) => $status->label(), self::cases())
        );
    }

    /**
     * 检查是否为活跃状态
     */
    public function isActive(): bool
    {
        return in_array($this, [self::PENDING, self::IN_PROGRESS]);
    }

    /**
     * 检查是否为完成状态
     */
    public function isCompleted(): bool
    {
        return $this === self::COMPLETED;
    }

    /**
     * 检查是否为终止状态
     */
    public function isTerminated(): bool
    {
        return in_array($this, [self::COMPLETED, self::CANCELLED]);
    }

    /**
     * 检查是否可以转换到指定状态
     */
    public function canTransitionTo(TaskStatus $newStatus): bool
    {
        return match($this) {
            self::PENDING => in_array($newStatus, [self::IN_PROGRESS, self::BLOCKED, self::CANCELLED, self::ON_HOLD]),
            self::IN_PROGRESS => in_array($newStatus, [self::COMPLETED, self::BLOCKED, self::CANCELLED, self::ON_HOLD]),
            self::BLOCKED => in_array($newStatus, [self::PENDING, self::IN_PROGRESS, self::CANCELLED, self::ON_HOLD]),
            self::ON_HOLD => in_array($newStatus, [self::PENDING, self::IN_PROGRESS, self::CANCELLED]),
            self::COMPLETED => false, // 已完成的任务不能再改变状态
            self::CANCELLED => false, // 已取消的任务不能再改变状态
        };
    }

    /**
     * 获取可以转换到的状态列表
     */
    public function getAvailableTransitions(): array
    {
        return array_filter(
            self::cases(),
            fn(TaskStatus $status) => $this->canTransitionTo($status)
        );
    }
}
