<?php

namespace App\Modules\Dbcont\Services;

use App\Modules\Dbcont\Models\AgentDatabasePermission;
use App\Modules\Dbcont\Models\DatabaseConnection;
use App\Modules\Dbcont\Enums\PermissionLevel;

class PermissionService
{
    /**
     * 检查Agent是否有数据库访问权限
     */
    public function hasConnectionAccess(int $agentId, int $connectionId): bool
    {
        return AgentDatabasePermission::where('agent_id', $agentId)
            ->where('database_connection_id', $connectionId)
            ->exists();
    }

    /**
     * 检查Agent是否有表访问权限
     */
    public function hasTableAccess(int $agentId, int $connectionId, string $table): bool
    {
        $permission = AgentDatabasePermission::where('agent_id', $agentId)
            ->where('database_connection_id', $connectionId)
            ->first();

        if (!$permission) {
            return false;
        }

        // 管理员权限可以访问所有表
        if ($permission->permission_level === PermissionLevel::ADMIN) {
            return true;
        }

        // 检查允许访问的表
        $allowedTables = $permission->allowed_tables ?? [];
        if (!empty($allowedTables) && !in_array($table, $allowedTables)) {
            return false;
        }

        return true;
    }

    /**
     * 检查Agent是否有操作权限
     */
    public function hasOperationPermission(int $agentId, int $connectionId, string $operation): bool
    {
        $permission = AgentDatabasePermission::where('agent_id', $agentId)
            ->where('database_connection_id', $connectionId)
            ->first();

        if (!$permission) {
            return false;
        }

        $operation = strtoupper(trim($operation));

        switch ($permission->permission_level) {
            case PermissionLevel::READ_ONLY:
                return $operation === 'SELECT';
            
            case PermissionLevel::READ_WRITE:
                return in_array($operation, ['SELECT', 'INSERT', 'UPDATE', 'DELETE']);
            
            case PermissionLevel::ADMIN:
                return true;
            
            default:
                return false;
        }
    }

    /**
     * 获取Agent的权限级别
     */
    public function getPermissionLevel(int $agentId, int $connectionId): PermissionLevel
    {
        $permission = AgentDatabasePermission::where('agent_id', $agentId)
            ->where('database_connection_id', $connectionId)
            ->first();

        if (!$permission) {
            return PermissionLevel::READ_ONLY;
        }

        return $permission->permission_level;
    }

    /**
     * 设置Agent权限
     */
    public function setAgentPermission(int $agentId, int $connectionId, array $permissions): void
    {
        AgentDatabasePermission::updateOrCreate(
            [
                'agent_id' => $agentId,
                'database_connection_id' => $connectionId,
            ],
            [
                'permission_level' => $permissions['permission_level'] ?? PermissionLevel::READ_ONLY,
                'allowed_tables' => $permissions['allowed_tables'] ?? null,
                'denied_operations' => $permissions['denied_operations'] ?? null,
                'max_query_time' => $permissions['max_query_time'] ?? 30,
                'max_result_rows' => $permissions['max_result_rows'] ?? 1000,
            ]
        );
    }

    /**
     * 获取Agent的所有权限
     */
    public function getAgentPermissions(int $agentId): array
    {
        $permissions = AgentDatabasePermission::where('agent_id', $agentId)
            ->with('databaseConnection')
            ->get();

        return $permissions->map(function ($permission) {
            return [
                'connection_id' => $permission->database_connection_id,
                'connection_name' => $permission->databaseConnection->name,
                'permission_level' => $permission->permission_level->value,
                'allowed_tables' => $permission->allowed_tables,
                'denied_operations' => $permission->denied_operations,
                'max_query_time' => $permission->max_query_time,
                'max_result_rows' => $permission->max_result_rows,
            ];
        })->toArray();
    }

    /**
     * 移除Agent权限
     */
    public function removeAgentPermission(int $agentId, int $connectionId): void
    {
        AgentDatabasePermission::where('agent_id', $agentId)
            ->where('database_connection_id', $connectionId)
            ->delete();
    }

    /**
     * 检查查询是否超出限制
     */
    public function checkQueryLimits(int $agentId, int $connectionId, string $sql): array
    {
        $permission = AgentDatabasePermission::where('agent_id', $agentId)
            ->where('database_connection_id', $connectionId)
            ->first();

        if (!$permission) {
            return [
                'allowed' => false,
                'message' => '没有权限访问此数据库连接',
            ];
        }

        // 检查表权限
        $tables = $this->extractTablesFromSql($sql);
        foreach ($tables as $table) {
            if (!$this->hasTableAccess($agentId, $connectionId, $table)) {
                return [
                    'allowed' => false,
                    'message' => "没有权限访问表: {$table}",
                ];
            }
        }

        return [
            'allowed' => true,
            'max_query_time' => $permission->max_query_time,
            'max_result_rows' => $permission->max_result_rows,
        ];
    }

    /**
     * 从SQL中提取表名
     */
    private function extractTablesFromSql(string $sql): array
    {
        // 简单的表名提取，实际项目中可能需要更复杂的解析
        $tables = [];
        
        // 匹配FROM和JOIN子句中的表名
        preg_match_all('/\bFROM\s+`?([a-zA-Z_][a-zA-Z0-9_]*)`?|\bJOIN\s+`?([a-zA-Z_][a-zA-Z0-9_]*)`?/i', $sql, $matches);
        
        foreach ($matches[1] as $table) {
            if ($table) {
                $tables[] = $table;
            }
        }
        
        foreach ($matches[2] as $table) {
            if ($table) {
                $tables[] = $table;
            }
        }
        
        return array_unique($tables);
    }
}