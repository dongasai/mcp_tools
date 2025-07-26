<?php

namespace Modules\Dbcont\Exceptions;

use Exception;

class DatabaseConnectionException extends Exception
{
    /**
     * 连接ID
     *
     * @var int|null
     */
    protected ?int $connectionId;

    /**
     * 创建新的异常实例
     */
    public function __construct(string $message = '', int $code = 0, ?Exception $previous = null, ?int $connectionId = null)
    {
        parent::__construct($message, $code, $previous);
        $this->connectionId = $connectionId;
    }

    /**
     * 获取连接ID
     */
    public function getConnectionId(): ?int
    {
        return $this->connectionId;
    }

    /**
     * 创建连接失败的异常
     */
    public static function connectionFailed(string $message, ?int $connectionId = null): self
    {
        return new static($message, 1001, null, $connectionId);
    }

    /**
     * 创建连接超时的异常
     */
    public static function connectionTimeout(string $message, ?int $connectionId = null): self
    {
        return new static($message, 1002, null, $connectionId);
    }

    /**
     * 创建认证失败的异常
     */
    public static function authenticationFailed(string $message, ?int $connectionId = null): self
    {
        return new static($message, 1003, null, $connectionId);
    }

    /**
     * 创建数据库不存在的异常
     */
    public static function databaseNotFound(string $message, ?int $connectionId = null): self
    {
        return new static($message, 1004, null, $connectionId);
    }
}