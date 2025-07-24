<?php

namespace App\Modules\Dbcont\Services;

use App\Modules\Dbcont\Contracts\DatabaseConnectionInterface;
use App\Modules\Dbcont\Models\DatabaseConnection;
use App\Modules\Dbcont\Enums\ConnectionStatus;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use App\Modules\Dbcont\Services\OperationLogService;

class DatabaseConnectionService implements DatabaseConnectionInterface
{
    public function __construct(
        private OperationLogService $logService
    ) {}

    /**
     * 创建数据库连接
     */
    public function createConnection(array $config): DatabaseConnection
    {
        // 加密密码
        if (isset($config['password'])) {
            $config['password'] = Crypt::encrypt($config['password']);
        }

        $connection = DatabaseConnection::create($config);
        
        // 记录操作日志
        $this->logService->log('创建数据库连接', [
            'action' => 'create_connection',
            'connection_id' => $connection->id,
            'name' => $connection->name,
            'type' => $connection->type,
            'database' => $connection->database
        ]);
        
        return $connection;
    }

    /**
     * 获取项目的数据库连接列表
     */
    public function getProjectConnections(int $projectId): Collection
    {
        return DatabaseConnection::where('project_id', $projectId)
            ->orderBy('name')
            ->get();
    }

    /**
     * 测试数据库连接
     */
    public function testConnection(int $connectionId): bool
    {
        $connection = DatabaseConnection::findOrFail($connectionId);
        $logData = [
            'action' => 'test_connection',
            'connection_id' => $connectionId,
            'name' => $connection->name,
            'type' => $connection->type,
            'database' => $connection->database
        ];
        
        try {
            $config = $this->buildConnectionConfig($connection);
            $pdo = $this->createPdoConnection($config);
            
            // 更新最后测试时间
            $connection->update([
                'status' => ConnectionStatus::ACTIVE,
                'last_tested_at' => now(),
            ]);
            
            // 记录成功日志
            $logData['status'] = 'success';
            $this->logService->log('测试数据库连接成功', $logData);
            
            return true;
        } catch (\Exception $e) {
            // 更新状态为错误
            $connection->update([
                'status' => ConnectionStatus::ERROR,
                'last_tested_at' => now(),
            ]);
            
            // 记录失败日志
            $logData['status'] = 'error';
            $logData['error'] = $e->getMessage();
            $this->logService->log('测试数据库连接失败', $logData);
            
            return false;
        }
    }

    /**
     * 获取连接状态
     */
    public function getConnectionStatus(int $connectionId): array
    {
        $connection = DatabaseConnection::findOrFail($connectionId);
        
        return [
            'id' => $connection->id,
            'name' => $connection->name,
            'type' => $connection->type->value,
            'status' => $connection->status->value,
            'last_tested_at' => $connection->last_tested_at,
            'database' => $connection->database,
        ];
    }

    /**
     * 获取数据库表列表
     */
    public function getTables(int $connectionId): array
    {
        $connection = DatabaseConnection::findOrFail($connectionId);
        $config = $this->buildConnectionConfig($connection);
        
        try {
            $pdo = $this->createPdoConnection($config);
            
            switch ($connection->type->value) {
                case 'MYSQL':
                case 'MARIADB':
                    $sql = "SHOW TABLES";
                    break;
                case 'SQLITE':
                    $sql = "SELECT name FROM sqlite_master WHERE type='table'";
                    break;
                default:
                    throw new \Exception("不支持的数据库类型: {$connection->type->value}");
            }
            
            $stmt = $pdo->query($sql);
            $tables = [];
            
            while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
                $tables[] = array_values($row)[0];
            }
            
            return $tables;
        } catch (\Exception $e) {
            throw new \Exception("获取表列表失败: " . $e->getMessage());
        }
    }

    /**
     * 获取表结构信息
     */
    public function getTableSchema(int $connectionId, string $table): array
    {
        $connection = DatabaseConnection::findOrFail($connectionId);
        $config = $this->buildConnectionConfig($connection);
        
        try {
            $pdo = $this->createPdoConnection($config);
            
            switch ($connection->type->value) {
                case 'MYSQL':
                case 'MARIADB':
                    $sql = "DESCRIBE `{$table}`";
                    break;
                case 'SQLITE':
                    $sql = "PRAGMA table_info(`{$table}`)";
                    break;
                default:
                    throw new \Exception("不支持的数据库类型: {$connection->type->value}");
            }
            
            $stmt = $pdo->query($sql);
            $columns = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            
            return [
                'table' => $table,
                'columns' => $columns,
            ];
        } catch (\Exception $e) {
            throw new \Exception("获取表结构失败: " . $e->getMessage());
        }
    }

    /**
     * 构建连接配置
     */
    private function buildConnectionConfig(DatabaseConnection $connection): array
    {
        $config = [
            'driver' => strtolower($connection->type->value),
            'database' => $connection->database,
            'options' => $connection->options ?? [],
        ];

        if ($connection->host) {
            $config['host'] = $connection->host;
        }

        if ($connection->port) {
            $config['port'] = $connection->port;
        }

        if ($connection->username) {
            $config['username'] = $connection->username;
        }

        if ($connection->password) {
            $config['password'] = \Illuminate\Support\Facades\Crypt::decrypt($connection->password);
        }

        return $config;
    }

    /**
     * 创建PDO连接
     */
    private function createPdoConnection(array $config): \PDO
    {
        $driver = $config['driver'];
        $dsn = '';

        switch ($driver) {
            case 'mysql':
            case 'mariadb':
                $dsn = "mysql:host={$config['host']};port={$config['port']};dbname={$config['database']}";
                break;
            case 'sqlite':
                $dsn = "sqlite:{$config['database']}";
                break;
            default:
                throw new \Exception("不支持的数据库驱动: {$driver}");
        }

        $username = $config['username'] ?? null;
        $password = $config['password'] ?? null;
        $options = $config['options'] ?? [];

        return new \PDO($dsn, $username, $password, $options);
    }
}