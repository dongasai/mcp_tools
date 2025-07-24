<?php

namespace App\Modules\Dbcont\Exceptions;

use Exception;

class SqlExecutionException extends Exception
{
    /**
     * SQL语句
     *
     * @var string|null
     */
    protected ?string $sql;

    /**
     * 连接ID
     *
     * @var int|null
     */
    protected ?int $connectionId;

    /**
     * 创建新的异常实例
     */
    public function __construct(string $message = '', int $code = 0, ?Exception $previous = null, ?string $sql = null, ?int $connectionId = null)
    {
        parent::__construct($message, $code, $previous);
        $this->sql = $sql;
        $this->connectionId = $connectionId;
    }

    /**
     * 获取SQL语句
     */
    public function getSql(): ?string
    {
        return $this->sql;
    }

    /**
     * 获取连接ID
     */
    public function getConnectionId(): ?int
    {
        return $this->connectionId;
    }

    /**
     * 创建语法错误的异常
     */
    public static function syntaxError(string $message, ?string $sql = null, ?int $connectionId = null): self
    {
        return new static($message, 2001, null, $sql, $connectionId);
    }

    /**
     * 创建权限不足的异常
     */
    public static function permissionDenied(string $message, ?string $sql = null, ?int $connectionId = null): self
    {
        return new static($message, 2002, null, $sql, $connectionId);
    }

    /**
     * 创建超时的异常
     */
    public static function timeout(string $message, ?string $sql = null, ?int $connectionId = null): self
    {
        return new static($message, 2003, null, $sql, $connectionId);
    }

    /**
     * 创建结果集过大的异常
     */
    public static function resultTooLarge(string $message, ?string $sql = null, ?int $connectionId = null): self
    {
        return new static($message, 2004, null, $sql, $connectionId);
    }

    /**
     * 创建表不存在的异常
     */
    public static function tableNotFound(string $message, ?string $sql = null, ?int $connectionId = null): self
    {
        return new static($message, 2005, null, $sql, $connectionId);
    }

    /**
     * 创建列不存在的异常
     */
    public static function columnNotFound(string $message, ?string $sql = null, ?int $connectionId = null): self
    {
        return new static($message, 2006, null, $sql, $connectionId);
    }
}