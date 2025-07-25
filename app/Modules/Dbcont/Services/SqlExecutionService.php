<?php

namespace App\Modules\Dbcont\Services;

use App\Modules\Dbcont\Contracts\SqlExecutionInterface;
use App\Modules\Dbcont\Models\DatabaseConnection;
use App\Modules\Dbcont\Models\SqlExecutionLog;
use App\Modules\Dbcont\Enums\PermissionLevel;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use App\Modules\Dbcont\Services\OperationLogService;

class SqlExecutionService implements SqlExecutionInterface
{
    public function __construct(
        private PermissionService $permissionService,
        private SecurityService $securityService,
        private OperationLogService $logService
    ) {}

    /**
     * 执行SQL查询
     */
    public function executeQuery(int $connectionId, string $sql, int $agentId, array $options = []): array
    {
        // 检查权限
        if (!$this->permissionService->hasConnectionAccess($agentId, $connectionId)) {
            throw new \Exception('Agent无权访问此数据库连接');
        }

        // 获取权限级别
        $permissionLevel = $this->permissionService->getPermissionLevel($agentId, $connectionId);
        
        // 验证SQL语句
        if (!$this->validateSql($sql, $permissionLevel)) {
            throw new \Exception('SQL语句验证失败');
        }

        // 获取数据库连接
        $connection = DatabaseConnection::findOrFail($connectionId);
        
        // 记录开始时间
        $startTime = microtime(true);
        
        // 准备操作日志数据
        $logData = [
            'action' => 'execute_query',
            'connection_id' => $connectionId,
            'agent_id' => $agentId,
            'sql' => $sql,
            'permission_level' => $permissionLevel->value
        ];
        
        try {
            // 创建数据库连接
            $dbConnection = $this->createDatabaseConnection($connection);
            
            // 执行查询
            $result = $this->executeStatement($dbConnection, $sql, $options);
            
            // 计算执行时间
            $executionTime = (int)(microtime(true) - $startTime) * 1000;
            
            // 记录执行日志
            $this->logExecution($agentId, $connectionId, $sql, $executionTime, $result);
            
            // 记录操作日志（成功）
            $logData['status'] = 'success';
            $logData['execution_time'] = $executionTime;
            $logData['affected_rows'] = $result['affected_rows'] ?? 0;
            $this->logService->log('执行SQL查询成功', $logData);
            
            return [
                'success' => true,
                'data' => $result['data'] ?? null,
                'affected_rows' => $result['affected_rows'] ?? 0,
                'execution_time' => $executionTime,
            ];
            
        } catch (\Exception $e) {
            // 记录错误日志
            $executionTime = (int)(microtime(true) - $startTime) * 1000;
            $this->logExecution($agentId, $connectionId, $sql, $executionTime, [], $e->getMessage());
            
            // 记录操作日志（失败）
            $logData['status'] = 'error';
            $logData['error'] = $e->getMessage();
            $this->logService->log('执行SQL查询失败', $logData);
            
            throw $e;
        }
    }

    /**
     * 执行结构化查询
     */
    public function executeStructuredQuery(int $connectionId, array $queryParams, int $agentId): array
    {
        // 构建SQL语句
        $sql = $this->buildSqlFromParams($queryParams);
        
        // 记录结构化查询日志
        $this->logService->log('执行结构化查询', [
            'action' => 'execute_structured_query',
            'connection_id' => $connectionId,
            'agent_id' => $agentId,
            'query_params' => $queryParams,
            'generated_sql' => $sql
        ]);
        
        return $this->executeQuery($connectionId, $sql, $agentId, $queryParams['options'] ?? []);
    }

    /**
     * 验证SQL语句安全性
     */
    public function validateSql(string $sql, PermissionLevel $level): bool
    {
        return $this->securityService->validateSql($sql, $level);
    }

    /**
     * 获取查询执行历史
     */
    public function getExecutionHistory(int $agentId, array $filters = []): Collection
    {
        $query = SqlExecutionLog::where('agent_id', $agentId)
            ->with('databaseConnection')
            ->orderBy('executed_at', 'desc');

        if (isset($filters['connection_id'])) {
            $query->where('database_connection_id', $filters['connection_id']);
        }

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['date_from'])) {
            $query->where('executed_at', '>=', $filters['date_from']);
        }

        if (isset($filters['date_to'])) {
            $query->where('executed_at', '<=', $filters['date_to']);
        }

        return $query->get();
    }

    /**
     * 创建数据库连接
     */
    private function createDatabaseConnection(DatabaseConnection $connection): \PDO
    {
        // 解密密码
        $password = $connection->password ? \Illuminate\Support\Facades\Crypt::decrypt($connection->password) : null;

        // 构建DSN
        $driver = strtolower($connection->driver);
        $dsn = '';

        switch ($driver) {
            case 'mysql':
            case 'mariadb':
                $dsn = "mysql:host={$connection->host};port={$connection->port};dbname={$connection->database}";
                break;
            case 'sqlite':
                $dsn = "sqlite:{$connection->database}";
                break;
            case 'pgsql':
                $dsn = "pgsql:host={$connection->host};port={$connection->port};dbname={$connection->database}";
                break;
            default:
                throw new \Exception("不支持的数据库驱动: {$driver}");
        }

        $options = array_merge([
            \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
            \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
            \PDO::ATTR_TIMEOUT => 30,
        ], $connection->options ?? []);

        return new \PDO($dsn, $connection->username, $password, $options);
    }

    /**
     * 执行SQL语句
     */
    private function executeStatement(\PDO $pdo, string $sql, array $options): array
    {
        $stmt = $pdo->prepare($sql);
        
        // 设置查询超时
        if (isset($options['timeout'])) {
            $pdo->setAttribute(\PDO::ATTR_TIMEOUT, $options['timeout']);
        }
        
        $stmt->execute();
        
        // 获取结果
        $result = [];
        
        // 检查是否是SELECT查询
        if (stripos(trim($sql), 'SELECT') === 0) {
            $result['data'] = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        } else {
            $result['affected_rows'] = $stmt->rowCount();
        }
        
        return $result;
    }

    /**
     * 从参数构建SQL
     */
    private function buildSqlFromParams(array $params): string
    {
        $type = $params['type'] ?? 'SELECT';
        $table = $params['table'] ?? '';
        $columns = $params['columns'] ?? ['*'];
        $where = $params['where'] ?? [];
        $order = $params['order'] ?? [];
        $limit = $params['limit'] ?? null;
        
        $sql = strtoupper($type) . ' ' . implode(', ', $columns) . ' FROM ' . $table;
        
        if (!empty($where)) {
            $conditions = [];
            foreach ($where as $condition) {
                $conditions[] = $condition['column'] . ' ' . $condition['operator'] . ' ' . $this->quoteValue($condition['value']);
            }
            $sql .= ' WHERE ' . implode(' AND ', $conditions);
        }
        
        if (!empty($order)) {
            $orders = [];
            foreach ($order as $orderItem) {
                $orders[] = $orderItem['column'] . ' ' . strtoupper($orderItem['direction']);
            }
            $sql .= ' ORDER BY ' . implode(', ', $orders);
        }
        
        if ($limit) {
            $sql .= ' LIMIT ' . (int)$limit;
        }
        
        return $sql;
    }

    /**
     * 引用值
     */
    private function quoteValue($value): string
    {
        if (is_string($value)) {
            return "'" . addslashes($value) . "'";
        }
        
        if (is_numeric($value)) {
            return (string)$value;
        }
        
        if (is_bool($value)) {
            return $value ? '1' : '0';
        }
        
        return 'NULL';
    }

    /**
     * 记录执行日志
     */
    private function logExecution(int $agentId, int $connectionId, string $sql, int $executionTime, array $result, ?string $error = null): void
    {
        $data = $result['data'] ?? [];
        $affectedRows = $result['affected_rows'] ?? 0;
        
        SqlExecutionLog::create([
            'agent_id' => $agentId,
            'database_connection_id' => $connectionId,
            'sql_statement' => $sql,
            'execution_time' => $executionTime,
            'rows_affected' => $affectedRows,
            'result_size' => strlen(json_encode($data)),
            'status' => $error ? 'ERROR' : 'SUCCESS',
            'error_message' => $error,
            'ip_address' => request()->ip() ?? '0.0.0.0',
            'executed_at' => now(),
        ]);
    }

    /**
     * 测试数据库连接
     */
    public function testConnection(DatabaseConnection $connection): array
    {
        try {
            $startTime = microtime(true);

            // 创建数据库连接
            $pdo = $this->createDatabaseConnection($connection);

            // 执行简单的测试查询
            $testSql = match($connection->driver) {
                'mysql', 'mariadb' => 'SELECT 1 as test',
                'sqlite' => 'SELECT 1 as test',
                'pgsql' => 'SELECT 1 as test',
                default => 'SELECT 1 as test'
            };

            $stmt = $pdo->prepare($testSql);
            $stmt->execute();
            $result = $stmt->fetch(\PDO::FETCH_ASSOC);

            $testTime = round((microtime(true) - $startTime) * 1000, 2); // 毫秒

            // 记录成功的连接测试
            $this->logService->log('数据库连接测试成功', [
                'action' => 'test_connection',
                'connection_id' => $connection->id,
                'connection_name' => $connection->name,
                'test_time_ms' => $testTime,
                'status' => 'success'
            ]);

            return [
                'success' => true,
                'message' => '连接测试成功',
                'test_time_ms' => $testTime,
                'test_result' => $result,
                'connection_info' => [
                    'driver' => $connection->driver,
                    'host' => $connection->host,
                    'database' => $connection->database,
                ]
            ];

        } catch (\Exception $e) {
            // 记录失败的连接测试
            $this->logService->log('数据库连接测试失败', [
                'action' => 'test_connection',
                'connection_id' => $connection->id,
                'connection_name' => $connection->name,
                'error' => $e->getMessage(),
                'status' => 'failed'
            ]);

            return [
                'success' => false,
                'message' => '连接测试失败: ' . $e->getMessage(),
                'error_code' => $e->getCode(),
                'connection_info' => [
                    'driver' => $connection->driver,
                    'host' => $connection->host,
                    'database' => $connection->database,
                ]
            ];
        }
    }
}