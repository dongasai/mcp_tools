<?php

namespace App\Modules\Mcp\Enums;

enum QuestionPriority: string
{
    case URGENT = 'URGENT';
    case HIGH = 'HIGH';
    case MEDIUM = 'MEDIUM';
    case LOW = 'LOW';

    /**
     * 获取优先级标签
     */
    public function label(): string
    {
        return match($this) {
            self::URGENT => '紧急',
            self::HIGH => '高',
            self::MEDIUM => '中',
            self::LOW => '低',
        };
    }

    /**
     * 获取优先级颜色
     */
    public function color(): string
    {
        return match($this) {
            self::URGENT => 'danger',
            self::HIGH => 'warning',
            self::MEDIUM => 'info',
            self::LOW => 'secondary',
        };
    }

    /**
     * 获取优先级数值（用于排序）
     */
    public function value(): int
    {
        return match($this) {
            self::URGENT => 1,
            self::HIGH => 2,
            self::MEDIUM => 3,
            self::LOW => 4,
        };
    }

    /**
     * 获取所有优先级选项
     */
    public static function options(): array
    {
        return [
            self::URGENT->value => self::URGENT->label(),
            self::HIGH->value => self::HIGH->label(),
            self::MEDIUM->value => self::MEDIUM->label(),
            self::LOW->value => self::LOW->label(),
        ];
    }

    /**
     * 获取所有优先级键值对
     */
    public static function keyValuePairs(): array
    {
        return [
            self::URGENT->value => self::URGENT->label(),
            self::HIGH->value => self::HIGH->label(),
            self::MEDIUM->value => self::MEDIUM->label(),
            self::LOW->value => self::LOW->label(),
        ];
    }

    /**
     * 从字符串值创建枚举实例
     */
    public static function fromString(string $value): ?self
    {
        return self::tryFrom($value);
    }
}
