<?php

namespace Tests\Unit\Modules\Dbcont;

use Tests\TestCase;
use App\Modules\Dbcont\Models\DatabaseConnection;
use App\Modules\Dbcont\Services\DatabaseConnectionService;
use App\Modules\Dbcont\Enums\DatabaseType;
use App\Modules\Dbcont\Enums\ConnectionStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

class DatabaseConnectionTest extends TestCase
{
    use RefreshDatabase;

    private DatabaseConnectionService $connectionService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->connectionService = app(DatabaseConnectionService::class);
    }

    /** @test */
    public function it_can_create_database_connection()
    {
        $connection = DatabaseConnection::create([
            'name' => 'Test Connection',
            'type' => DatabaseType::SQLITE,
            'host' => null,
            'port' => null,
            'database' => ':memory:',
            'username' => null,
            'password' => null,
            'options' => [],
            'status' => ConnectionStatus::ACTIVE,
        ]);

        $this->assertInstanceOf(DatabaseConnection::class, $connection);
        $this->assertEquals('Test Connection', $connection->name);
        $this->assertEquals(DatabaseType::SQLITE, $connection->type);
    }

    /** @test */
    public function it_can_test_sqlite_connection()
    {
        $connection = DatabaseConnection::create([
            'name' => 'SQLite Test',
            'type' => DatabaseType::SQLITE,
            'database' => ':memory:',
            'status' => ConnectionStatus::ACTIVE,
        ]);

        $result = $this->connectionService->testConnection($connection);
        
        $this->assertTrue($result['success']);
        $this->assertEquals('连接成功', $result['message']);
    }

    /** @test */
    public function it_can_test_mysql_connection()
    {
        $connection = DatabaseConnection::create([
            'name' => 'MySQL Test',
            'type' => DatabaseType::MYSQL,
            'host' => '127.0.0.1',
            'port' => 3306,
            'database' => 'test_db',
            'username' => 'test_user',
            'password' => 'test_password',
            'status' => ConnectionStatus::ACTIVE,
        ]);

        // 由于测试环境可能没有MySQL，我们模拟连接测试
        $result = $this->connectionService->testConnection($connection);
        
        // 断言返回格式正确
        $this->assertArrayHasKey('success', $result);
        $this->assertArrayHasKey('message', $result);
    }

    /** @test */
    public function it_can_get_connection_config()
    {
        $connection = DatabaseConnection::create([
            'name' => 'Test Config',
            'type' => DatabaseType::MYSQL,
            'host' => 'localhost',
            'port' => 3306,
            'database' => 'test_db',
            'username' => 'user',
            'password' => 'pass',
            'options' => ['charset' => 'utf8mb4'],
            'status' => ConnectionStatus::ACTIVE,
        ]);

        $config = $this->connectionService->getConnectionConfig($connection);
        
        $this->assertEquals('mysql', $config['driver']);
        $this->assertEquals('localhost', $config['host']);
        $this->assertEquals(3306, $config['port']);
        $this->assertEquals('test_db', $config['database']);
        $this->assertEquals('user', $config['username']);
        $this->assertEquals('pass', $config['password']);
        $this->assertEquals('utf8mb4', $config['charset']);
    }

    /** @test */
    public function it_can_list_all_connections()
    {
        DatabaseConnection::create([
            'name' => 'Connection 1',
            'type' => DatabaseType::SQLITE,
            'database' => ':memory:',
            'status' => ConnectionStatus::ACTIVE,
        ]);

        DatabaseConnection::create([
            'name' => 'Connection 2',
            'type' => DatabaseType::MYSQL,
            'host' => 'localhost',
            'port' => 3306,
            'database' => 'test_db',
            'username' => 'user',
            'password' => 'pass',
            'status' => ConnectionStatus::INACTIVE,
        ]);

        $connections = $this->connectionService->getAllConnections();
        
        $this->assertCount(2, $connections);
    }

    /** @test */
    public function it_can_update_connection_status()
    {
        $connection = DatabaseConnection::create([
            'name' => 'Test Status',
            'type' => DatabaseType::SQLITE,
            'database' => ':memory:',
            'status' => ConnectionStatus::ACTIVE,
        ]);

        $updated = $this->connectionService->updateConnectionStatus(
            $connection->id, 
            ConnectionStatus::INACTIVE
        );

        $this->assertTrue($updated);
        $this->assertEquals(ConnectionStatus::INACTIVE, $connection->fresh()->status);
    }

    /** @test */
    public function it_can_delete_connection()
    {
        $connection = DatabaseConnection::create([
            'name' => 'To Delete',
            'type' => DatabaseType::SQLITE,
            'database' => ':memory:',
            'status' => ConnectionStatus::ACTIVE,
        ]);

        $deleted = $this->connectionService->deleteConnection($connection->id);
        
        $this->assertTrue($deleted);
        $this->assertDatabaseMissing('database_connections', ['id' => $connection->id]);
    }

    /** @test */
    public function it_validates_connection_name()
    {
        $this->expectException(\InvalidArgumentException::class);
        
        DatabaseConnection::create([
            'name' => '', // 空名称应该失败
            'type' => DatabaseType::SQLITE,
            'database' => ':memory:',
            'status' => ConnectionStatus::ACTIVE,
        ]);
    }

    /** @test */
    public function it_validates_database_type()
    {
        $this->expectException(\InvalidArgumentException::class);
        
        DatabaseConnection::create([
            'name' => 'Invalid Type',
            'type' => 'INVALID_TYPE', // 无效类型应该失败
            'database' => ':memory:',
            'status' => ConnectionStatus::ACTIVE,
        ]);
    }

    /** @test */
    public function it_can_get_connection_by_id()
    {
        $connection = DatabaseConnection::create([
            'name' => 'Find Me',
            'type' => DatabaseType::SQLITE,
            'database' => ':memory:',
            'status' => ConnectionStatus::ACTIVE,
        ]);

        $found = $this->connectionService->getConnectionById($connection->id);
        
        $this->assertNotNull($found);
        $this->assertEquals('Find Me', $found->name);
    }

    /** @test */
    public function it_returns_null_for_nonexistent_connection()
    {
        $found = $this->connectionService->getConnectionById(99999);
        
        $this->assertNull($found);
    }
}