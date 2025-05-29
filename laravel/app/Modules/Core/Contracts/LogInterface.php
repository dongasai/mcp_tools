<?php

namespace App\Modules\Core\Contracts;

interface LogInterface
{
    /**
     * 记录调试信息
     */
    public function debug(string $message, array $context = []): void;

    /**
     * 记录信息
     */
    public function info(string $message, array $context = []): void;

    /**
     * 记录警告
     */
    public function warning(string $message, array $context = []): void;

    /**
     * 记录错误
     */
    public function error(string $message, array $context = []): void;

    /**
     * 记录严重错误
     */
    public function critical(string $message, array $context = []): void;

    /**
     * 记录性能日志
     */
    public function performance(string $operation, float $duration, array $context = []): void;

    /**
     * 记录审计日志
     */
    public function audit(string $action, string $user, array $data = []): void;

    /**
     * 记录安全日志
     */
    public function security(string $event, array $context = []): void;

    /**
     * 设置日志上下文
     */
    public function withContext(array $context): self;

    /**
     * 设置日志渠道
     */
    public function channel(string $channel): self;
}
