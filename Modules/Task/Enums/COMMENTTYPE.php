<?php

namespace Modules\Task\Enums;

enum COMMENTTYPE: string
{
    case GENERAL = 'general';
    case STATUS_UPDATE = 'status_update';
    case PROGRESS_REPORT = 'progress_report';
    case ISSUE_REPORT = 'issue_report';
    case SOLUTION = 'solution';
    case QUESTION = 'question';
    case ANSWER = 'answer';
    case SYSTEM = 'system';

    /**
     * 获取评论类型的中文标签
     */
    public function label(): string
    {
        return match($this) {
            self::GENERAL => '一般讨论',
            self::STATUS_UPDATE => '状态更新',
            self::PROGRESS_REPORT => '进度报告',
            self::ISSUE_REPORT => '问题报告',
            self::SOLUTION => '解决方案',
            self::QUESTION => '提问',
            self::ANSWER => '回答',
            self::SYSTEM => '系统通知',
        };
    }

    /**
     * 获取评论类型的颜色
     */
    public function color(): string
    {
        return match($this) {
            self::GENERAL => 'blue',
            self::STATUS_UPDATE => 'green',
            self::PROGRESS_REPORT => 'cyan',
            self::ISSUE_REPORT => 'red',
            self::SOLUTION => 'emerald',
            self::QUESTION => 'yellow',
            self::ANSWER => 'purple',
            self::SYSTEM => 'gray',
        };
    }

    /**
     * 获取评论类型的图标
     */
    public function icon(): string
    {
        return match($this) {
            self::GENERAL => 'fa-comment',
            self::STATUS_UPDATE => 'fa-info-circle',
            self::PROGRESS_REPORT => 'fa-chart-line',
            self::ISSUE_REPORT => 'fa-exclamation-triangle',
            self::SOLUTION => 'fa-lightbulb',
            self::QUESTION => 'fa-question-circle',
            self::ANSWER => 'fa-check-circle',
            self::SYSTEM => 'fa-cog',
        };
    }

    /**
     * 获取评论类型的描述
     */
    public function description(): string
    {
        return match($this) {
            self::GENERAL => '一般性讨论和交流',
            self::STATUS_UPDATE => '任务状态变更说明',
            self::PROGRESS_REPORT => 'Agent进度报告',
            self::ISSUE_REPORT => '问题和错误报告',
            self::SOLUTION => '问题解决方案',
            self::QUESTION => '提出问题和疑问',
            self::ANSWER => '回答问题',
            self::SYSTEM => '系统自动生成的通知',
        };
    }

    /**
     * 检查是否为系统类型
     */
    public function isSystem(): bool
    {
        return $this === self::SYSTEM;
    }

    /**
     * 检查是否为Agent类型
     */
    public function isAgentType(): bool
    {
        return in_array($this, [
            self::PROGRESS_REPORT,
            self::ISSUE_REPORT,
            self::SOLUTION,
            self::ANSWER,
        ]);
    }

    /**
     * 检查是否为用户类型
     */
    public function isUserType(): bool
    {
        return in_array($this, [
            self::GENERAL,
            self::STATUS_UPDATE,
            self::QUESTION,
        ]);
    }

    /**
     * 获取所有评论类型的选项数组
     */
    public static function selectOptions(): array
    {
        $options = [];
        foreach (self::cases() as $case) {
            $options[$case->value] = $case->label();
        }
        return $options;
    }

    /**
     * 获取用户可用的评论类型
     */
    public static function userOptions(): array
    {
        $options = [];
        foreach (self::cases() as $case) {
            if ($case->isUserType() || $case === self::GENERAL) {
                $options[$case->value] = $case->label();
            }
        }
        return $options;
    }

    /**
     * 获取Agent可用的评论类型
     */
    public static function agentOptions(): array
    {
        $options = [];
        foreach (self::cases() as $case) {
            if ($case->isAgentType() || $case === self::GENERAL) {
                $options[$case->value] = $case->label();
            }
        }
        return $options;
    }

    /**
     * 从字符串创建枚举实例
     */
    public static function fromString(string $value): ?self
    {
        foreach (self::cases() as $case) {
            if ($case->value === $value) {
                return $case;
            }
        }
        return null;
    }

    /**
     * 获取默认评论类型
     */
    public static function default(): self
    {
        return self::GENERAL;
    }

    /**
     * 获取评论类型的优先级（用于排序）
     */
    public function priority(): int
    {
        return match($this) {
            self::SYSTEM => 1,
            self::STATUS_UPDATE => 2,
            self::PROGRESS_REPORT => 3,
            self::ISSUE_REPORT => 4,
            self::SOLUTION => 5,
            self::QUESTION => 6,
            self::ANSWER => 7,
            self::GENERAL => 8,
        };
    }

    /**
     * 检查是否需要通知
     */
    public function shouldNotify(): bool
    {
        return !in_array($this, [self::SYSTEM]);
    }

    /**
     * 获取评论类型的CSS类
     */
    public function cssClass(): string
    {
        return 'comment-type-' . str_replace('_', '-', $this->value);
    }
}
