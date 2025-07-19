<?php

namespace App\Modules\Task\Enums;

enum TaskPriority: string
{
    case LOW = 'low';
    case MEDIUM = 'medium';
    case HIGH = 'high';
    case URGENT = 'urgent';

    /**
     * 获取优先级的显示名称
     */
    public function label(): string
    {
        return match($this) {
            self::LOW => 'Low',
            self::MEDIUM => 'Medium',
            self::HIGH => 'High',
            self::URGENT => 'Urgent',
        };
    }

    /**
     * 获取优先级的颜色
     */
    public function color(): string
    {
        return match($this) {
            self::LOW => 'secondary',
            self::MEDIUM => 'info',
            self::HIGH => 'warning',
            self::URGENT => 'danger',
        };
    }

    /**
     * 获取优先级的图标
     */
    public function icon(): string
    {
        return match($this) {
            self::LOW => 'fa-arrow-down',
            self::MEDIUM => 'fa-minus',
            self::HIGH => 'fa-arrow-up',
            self::URGENT => 'fa-exclamation',
        };
    }

    /**
     * 获取优先级的数值（用于排序）
     */
    public function value(): int
    {
        return match($this) {
            self::LOW => 1,
            self::MEDIUM => 2,
            self::HIGH => 3,
            self::URGENT => 4,
        };
    }

    /**
     * 获取所有优先级选项
     */
    public static function options(): array
    {
        return array_map(
            fn(TaskPriority $priority) => [
                'value' => $priority->value,
                'label' => $priority->label(),
                'color' => $priority->color(),
                'icon' => $priority->icon(),
                'sort_value' => $priority->value(),
            ],
            self::cases()
        );
    }

    /**
     * 获取优先级选项用于表单选择
     */
    public static function selectOptions(): array
    {
        return array_combine(
            array_map(fn(TaskPriority $priority) => $priority->value, self::cases()),
            array_map(fn(TaskPriority $priority) => $priority->label(), self::cases())
        );
    }

    /**
     * 检查是否为高优先级
     */
    public function isHigh(): bool
    {
        return in_array($this, [self::HIGH, self::URGENT]);
    }

    /**
     * 检查是否为紧急优先级
     */
    public function isUrgent(): bool
    {
        return $this === self::URGENT;
    }

    /**
     * 比较优先级
     */
    public function isHigherThan(TaskPriority $other): bool
    {
        return $this->value() > $other->value();
    }

    /**
     * 比较优先级
     */
    public function isLowerThan(TaskPriority $other): bool
    {
        return $this->value() < $other->value();
    }
}
