<?php

namespace Modules\Dbcont\Exceptions;

use Exception;

class PermissionDeniedException extends Exception
{
    /**
     * 权限类型
     *
     * @var string|null
     */
    protected ?string $permissionType;

    /**
     * 资源ID
     *
     * @var int|null
     */
    protected ?int $resourceId;

    /**
     * 创建新的异常实例
     */
    public function __construct(string $message = '', int $code = 0, ?Exception $previous = null, ?string $permissionType = null, ?int $resourceId = null)
    {
        parent::__construct($message, $code, $previous);
        $this->permissionType = $permissionType;
        $this->resourceId = $resourceId;
    }

    /**
     * 获取权限类型
     */
    public function getPermissionType(): ?string
    {
        return $this->permissionType;
    }

    /**
     * 获取资源ID
     */
    public function getResourceId(): ?int
    {
        return $this->resourceId;
    }

    /**
     * 创建连接权限不足的异常
     */
    public static function connectionAccessDenied(int $agentId, int $connectionId): self
    {
        return new static(
            "Agent {$agentId} 没有权限访问数据库连接 {$connectionId}",
            3001,
            null,
            'connection_access',
            $connectionId
        );
    }

    /**
     * 创建表权限不足的异常
     */
    public static function tableAccessDenied(int $agentId, string $table, int $connectionId): self
    {
        return new static(
            "Agent {$agentId} 没有权限访问表 {$table}",
            3002,
            null,
            'table_access',
            $connectionId
        );
    }

    /**
     * 创建操作权限不足的异常
     */
    public static function operationDenied(int $agentId, string $operation, int $connectionId): self
    {
        return new static(
            "Agent {$agentId} 没有权限执行操作 {$operation}",
            3003,
            null,
            'operation_permission',
            $connectionId
        );
    }

    /**
     * 创建查询限制超出的异常
     */
    public static function queryLimitExceeded(string $limitType, int $limitValue): self
    {
        return new static(
            "查询超出限制: {$limitType} 最大允许值为 {$limitValue}",
            3004,
            null,
            'query_limit'
        );
    }
}