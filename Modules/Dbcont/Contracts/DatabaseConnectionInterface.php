<?php

namespace Modules\Dbcont\Contracts;

use Modules\Dbcont\Models\DatabaseConnection;
use Illuminate\Database\Eloquent\Collection;

interface DatabaseConnectionInterface
{
    /**
     * 创建数据库连接
     *
     * @param array $config
     * @return DatabaseConnection
     */
    public function createConnection(array $config): DatabaseConnection;

    /**
     * 获取项目的数据库连接列表
     *
     * @param int $projectId
     * @return Collection
     */
    public function getProjectConnections(int $projectId): Collection;

    /**
     * 测试数据库连接
     *
     * @param int $connectionId
     * @return bool
     */
    public function testConnection(int $connectionId): bool;

    /**
     * 获取连接状态
     *
     * @param int $connectionId
     * @return array
     */
    public function getConnectionStatus(int $connectionId): array;

    /**
     * 获取数据库表列表
     *
     * @param int $connectionId
     * @return array
     */
    public function getTables(int $connectionId): array;

    /**
     * 获取表结构信息
     *
     * @param int $connectionId
     * @param string $table
     * @return array
     */
    public function getTableSchema(int $connectionId, string $table): array;
}