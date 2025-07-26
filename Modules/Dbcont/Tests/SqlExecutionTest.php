<?php

namespace Tests\Unit\Modules\Dbcont;

use Tests\TestCase;
use Modules\Dbcont\Models\DatabaseConnection;
use Modules\Dbcont\Models\AgentDatabasePermission;
use Modules\Dbcont\Models\SqlExecutionLog;
use Modules\Dbcont\Services\SqlExecutionService;
use Modules\Dbcont\Services\PermissionService;
use Modules\Dbcont\Enums\DatabaseType;
use Modules\Dbcont\Enums\ConnectionStatus;
use Modules\Dbcont\Enums\PermissionLevel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

class SqlExecutionTest extends TestCase
{
    use RefreshDatabase;

    private SqlExecutionService $executionService;
    private PermissionService $permissionService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->executionService = app(SqlExecutionService::class);
        $this->permissionService = app(PermissionService::class);
    }

    /** @test */
    public function it_can_execute_select_query()
    {
        $connection = DatabaseConnection::create([
            'name' => 'Test SQLite',
            'type' => DatabaseType::SQLITE,
            'database' => ':memory:',
            'status' => ConnectionStatus::ACTIVE,
        ]);

        $agentId = 1;
        
        // 创建权限
        AgentDatabasePermission::create([
            'agent_id' => $agentId,
            'database_connection_id' => $connection->id,
            'permission_level' => PermissionLevel::READ_ONLY,
        ]);

        // 创建测试表
        $this->executionService->executeSql(
            $connection->id,
            $agentId,
            'CREATE TABLE test_table (id INTEGER PRIMARY KEY, name TEXT)'
        );

        // 插入测试数据
        $this->executionService->executeSql(
            $connection->id,
            $agentId,
            "INSERT INTO test_table (name) VALUES ('Test Name')"
        );

        // 执行查询
        $result = $this->executionService->executeSql(
            $connection->id,
            $agentId,
            'SELECT * FROM test_table'
        );

        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('data', $result);
        $this->assertCount(1, $result['data']);
        $this->assertEquals('Test Name', $result['data'][0]['name']);
    }

    /** @test */
    public function it_logs_sql_execution()
    {
        $connection = DatabaseConnection::create([
            'name' => 'Test Logging',
            'type' => DatabaseType::SQLITE,
            'database' => ':memory:',
            'status' => ConnectionStatus::ACTIVE,
        ]);

        $agentId = 1;
        
        AgentDatabasePermission::create([
            'agent_id' => $agentId,
            'database_connection_id' => $connection->id,
            'permission_level' => PermissionLevel::READ_WRITE,
        ]);

        $this->executionService->executeSql(
            $connection->id,
            $agentId,
            'SELECT 1 as test'
        );

        $this->assertDatabaseHas('sql_execution_logs', [
            'database_connection_id' => $connection->id,
            'agent_id' => $agentId,
            'sql_statement' => 'SELECT 1 as test',
            'success' => true,
        ]);
    }

    /** @test */
    public function it_enforces_read_only_permission()
    {
        $connection = DatabaseConnection::create([
            'name' => 'Read Only Test',
            'type' => DatabaseType::SQLITE,
            'database' => ':memory:',
            'status' => ConnectionStatus::ACTIVE,
        ]);

        $agentId = 1;
        
        AgentDatabasePermission::create([
            'agent_id' => $agentId,
            'database_connection_id' => $connection->id,
            'permission_level' => PermissionLevel::READ_ONLY,
        ]);

        $result = $this->executionService->executeSql(
            $connection->id,
            $agentId,
            'CREATE TABLE test_table (id INTEGER PRIMARY KEY)'
        );

        $this->assertFalse($result['success']);
        $this->assertStringContains('权限不足', $result['message']);
    }

    /** @test */
    public function it_enforces_read_write_permission()
    {
        $connection = DatabaseConnection::create([
            'name' => 'Read Write Test',
            'type' => DatabaseType::SQLITE,
            'database' => ':memory:',
            'status' => ConnectionStatus::ACTIVE,
        ]);

        $agentId = 1;
        
        AgentDatabasePermission::create([
            'agent_id' => $agentId,
            'database_connection_id' => $connection->id,
            'permission_level' => PermissionLevel::READ_WRITE,
        ]);

        // 应该允许创建表
        $result = $this->executionService->executeSql(
            $connection->id,
            $agentId,
            'CREATE TABLE test_table (id INTEGER PRIMARY KEY)'
        );

        $this->assertTrue($result['success']);
    }

    /** @test */
    public function it_enforces_admin_permission()
    {
        $connection = DatabaseConnection::create([
            'name' => 'Admin Test',
            'type' => DatabaseType::SQLITE,
            'database' => ':memory:',
            'status' => ConnectionStatus::ACTIVE,
        ]);

        $agentId = 1;
        
        AgentDatabasePermission::create([
            'agent_id' => $agentId,
            'database_connection_id' => $connection->id,
            'permission_level' => PermissionLevel::ADMIN,
        ]);

        // 应该允许所有操作
        $result = $this->executionService->executeSql(
            $connection->id,
            $agentId,
            'DROP TABLE IF EXISTS test_table'
        );

        $this->assertTrue($result['success']);
    }

    /** @test */
    public function it_blocks_execution_without_permission()
    {
        $connection = DatabaseConnection::create([
            'name' => 'No Permission Test',
            'type' => DatabaseType::SQLITE,
            'database' => ':memory:',
            'status' => ConnectionStatus::ACTIVE,
        ]);

        $agentId = 1;
        // 不创建权限记录

        $result = $this->executionService->executeSql(
            $connection->id,
            $agentId,
            'SELECT 1'
        );

        $this->assertFalse($result['success']);
        $this->assertStringContains('没有权限', $result['message']);
    }

    /** @test */
    public function it_blocks_execution_on_inactive_connection()
    {
        $connection = DatabaseConnection::create([
            'name' => 'Inactive Test',
            'type' => DatabaseType::SQLITE,
            'database' => ':memory:',
            'status' => ConnectionStatus::INACTIVE,
        ]);

        $agentId = 1;
        
        AgentDatabasePermission::create([
            'agent_id' => $agentId,
            'database_connection_id' => $connection->id,
            'permission_level' => PermissionLevel::READ_WRITE,
        ]);

        $result = $this->executionService->executeSql(
            $connection->id,
            $agentId,
            'SELECT 1'
        );

        $this->assertFalse($result['success']);
        $this->assertStringContains('连接不可用', $result['message']);
    }

    /** @test */
    public function it_handles_invalid_sql_syntax()
    {
        $connection = DatabaseConnection::create([
            'name' => 'Invalid SQL Test',
            'type' => DatabaseType::SQLITE,
            'database' => ':memory:',
            'status' => ConnectionStatus::ACTIVE,
        ]);

        $agentId = 1;
        
        AgentDatabasePermission::create([
            'agent_id' => $agentId,
            'database_connection_id' => $connection->id,
            'permission_level' => PermissionLevel::READ_WRITE,
        ]);

        $result = $this->executionService->executeSql(
            $connection->id,
            $agentId,
            'INVALID SQL SYNTAX'
        );

        $this->assertFalse($result['success']);
        $this->assertStringContains('语法错误', $result['message']);
    }

    /** @test */
    public function it_can_get_execution_history()
    {
        $connection = DatabaseConnection::create([
            'name' => 'History Test',
            'type' => DatabaseType::SQLITE,
            'database' => ':memory:',
            'status' => ConnectionStatus::ACTIVE,
        ]);

        $agentId = 1;
        
        AgentDatabasePermission::create([
            'agent_id' => $agentId,
            'database_connection_id' => $connection->id,
            'permission_level' => PermissionLevel::READ_WRITE,
        ]);

        // 执行多个SQL语句
        $this->executionService->executeSql($connection->id, $agentId, 'SELECT 1');
        $this->executionService->executeSql($connection->id, $agentId, 'SELECT 2');
        $this->executionService->executeSql($connection->id, $agentId, 'SELECT 3');

        $history = $this->executionService->getExecutionHistory($connection->id, $agentId);
        
        $this->assertCount(3, $history);
        $this->assertInstanceOf(SqlExecutionLog::class, $history[0]);
    }

    /** @test */
    public function it_can_get_execution_history_with_pagination()
    {
        $connection = DatabaseConnection::create([
            'name' => 'Pagination Test',
            'type' => DatabaseType::SQLITE,
            'database' => ':memory:',
            'status' => ConnectionStatus::ACTIVE,
        ]);

        $agentId = 1;
        
        AgentDatabasePermission::create([
            'agent_id' => $agentId,
            'database_connection_id' => $connection->id,
            'permission_level' => PermissionLevel::READ_WRITE,
        ]);

        // 创建多个执行记录
        for ($i = 0; $i < 15; $i++) {
            SqlExecutionLog::create([
                'database_connection_id' => $connection->id,
                'agent_id' => $agentId,
                'sql_statement' => "SELECT {$i}",
                'success' => true,
                'execution_time' => 0.1,
                'affected_rows' => 1,
            ]);
        }

        $history = $this->executionService->getExecutionHistory($connection->id, $agentId, 10);
        
        $this->assertCount(10, $history);
    }

    /** @test */
    public function it_can_get_execution_statistics()
    {
        $connection = DatabaseConnection::create([
            'name' => 'Stats Test',
            'type' => DatabaseType::SQLITE,
            'database' => ':memory:',
            'status' => ConnectionStatus::ACTIVE,
        ]);

        $agentId = 1;
        
        AgentDatabasePermission::create([
            'agent_id' => $agentId,
            'database_connection_id' => $connection->id,
            'permission_level' => PermissionLevel::READ_WRITE,
        ]);

        // 创建成功和失败的记录
        SqlExecutionLog::create([
            'database_connection_id' => $connection->id,
            'agent_id' => $agentId,
            'sql_statement' => 'SELECT 1',
            'success' => true,
            'execution_time' => 0.1,
            'affected_rows' => 1,
        ]);

        SqlExecutionLog::create([
            'database_connection_id' => $connection->id,
            'agent_id' => $agentId,
            'sql_statement' => 'INVALID SQL',
            'success' => false,
            'execution_time' => 0.05,
            'affected_rows' => 0,
        ]);

        $stats = $this->executionService->getExecutionStatistics($connection->id, $agentId);
        
        $this->assertEquals(2, $stats['total_executions']);
        $this->assertEquals(1, $stats['successful_executions']);
        $this->assertEquals(1, $stats['failed_executions']);
        $this->assertEquals(0.075, $stats['average_execution_time']);
    }
}