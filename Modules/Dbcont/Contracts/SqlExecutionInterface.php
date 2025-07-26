<?php

namespace Modules\Dbcont\Contracts;

use Modules\Dbcont\Enums\PermissionLevel;
use Illuminate\Database\Eloquent\Collection;

interface SqlExecutionInterface
{
    /**
     * 执行SQL查询
     *
     * @param int $connectionId
     * @param string $sql
     * @param int $agentId
     * @param array $options
     * @return array
     */
    public function executeQuery(int $connectionId, string $sql, int $agentId, array $options = []): array;

    /**
     * 执行结构化查询
     *
     * @param int $connectionId
     * @param array $queryParams
     * @param int $agentId
     * @return array
     */
    public function executeStructuredQuery(int $connectionId, array $queryParams, int $agentId): array;

    /**
     * 验证SQL语句安全性
     *
     * @param string $sql
     * @param PermissionLevel $level
     * @return bool
     */
    public function validateSql(string $sql, PermissionLevel $level): bool;

    /**
     * 获取查询执行历史
     *
     * @param int $agentId
     * @param array $filters
     * @return Collection
     */
    public function getExecutionHistory(int $agentId, array $filters = []): Collection;
}