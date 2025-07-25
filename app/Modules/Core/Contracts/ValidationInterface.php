<?php

namespace App\Modules\Core\Contracts;

interface ValidationInterface
{
    /**
     * 验证数据
     */
    public function validate(array $data, array $rules, array $messages = []): array;

    /**
     * 验证单个字段
     */
    public function validateField(string $field, mixed $value, string|array $rules): bool;

    /**
     * 获取验证错误
     */
    public function getErrors(): array;

    /**
     * 检查是否有错误
     */
    public function hasErrors(): bool;

    /**
     * 添加自定义验证规则
     */
    public function addRule(string $name, callable $callback): void;

    /**
     * 设置错误消息
     */
    public function setMessages(array $messages): void;

    /**
     * 验证MCP消息格式
     */
    public function validateMCPMessage(array $message): array;

    /**
     * 验证Agent权限
     */
    public function validateAgentPermissions(string $agentId, array $permissions): bool;

    /**
     * 验证项目访问权限
     */
    public function validateProjectAccess(string $agentId, int $projectId): bool;

    /**
     * 批量验证
     */
    public function validateBatch(array $items, array $rules): array;
}
