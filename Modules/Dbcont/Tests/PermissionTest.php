<?php

namespace Tests\Unit\Modules\Dbcont;

use Tests\TestCase;
use Modules\Dbcont\Models\DatabaseConnection;
use Modules\Dbcont\Models\AgentDatabasePermission;
use Modules\Dbcont\Services\PermissionService;
use Modules\Dbcont\Enums\DatabaseType;
use Modules\Dbcont\Enums\ConnectionStatus;
use Modules\Dbcont\Enums\PermissionLevel;
use Illuminate\Foundation\Testing\RefreshDatabase;

class PermissionTest extends TestCase
{
    use RefreshDatabase;

    private PermissionService $permissionService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->permissionService = app(PermissionService::class);
    }

    /** @test */
    public function it_can_grant_permission_to_agent()
    {
        $connection = DatabaseConnection::create([
            'name' => 'Test Connection',
            'type' => DatabaseType::SQLITE,
            'database' => ':memory:',
            'status' => ConnectionStatus::ACTIVE,
        ]);

        $agentId = 1;

        $permission = $this->permissionService->grantPermission(
            $agentId,
            $connection->id,
            PermissionLevel::READ_WRITE
        );

        $this->assertInstanceOf(AgentDatabasePermission::class, $permission);
        $this->assertEquals($agentId, $permission->agent_id);
        $this->assertEquals($connection->id, $permission->database_connection_id);
        $this->assertEquals(PermissionLevel::READ_WRITE, $permission->permission_level);
    }

    /** @test */
    public function it_can_update_existing_permission()
    {
        $connection = DatabaseConnection::create([
            'name' => 'Test Connection',
            'type' => DatabaseType::SQLITE,
            'database' => ':memory:',
            'status' => ConnectionStatus::ACTIVE,
        ]);

        $agentId = 1;

        // 先授予READ_ONLY权限
        $this->permissionService->grantPermission(
            $agentId,
            $connection->id,
            PermissionLevel::READ_ONLY
        );

        // 更新为READ_WRITE权限
        $updated = $this->permissionService->grantPermission(
            $agentId,
            $connection->id,
            PermissionLevel::READ_WRITE
        );

        $this->assertTrue($updated);
        $this->assertEquals(
            PermissionLevel::READ_WRITE,
            AgentDatabasePermission::where('agent_id', $agentId)
                ->where('database_connection_id', $connection->id)
                ->first()
                ->permission_level
        );
    }

    /** @test */
    public function it_can_revoke_permission()
    {
        $connection = DatabaseConnection::create([
            'name' => 'Test Connection',
            'type' => DatabaseType::SQLITE,
            'database' => ':memory:',
            'status' => ConnectionStatus::ACTIVE,
        ]);

        $agentId = 1;

        // 先授予权限
        $this->permissionService->grantPermission(
            $agentId,
            $connection->id,
            PermissionLevel::READ_WRITE
        );

        // 撤销权限
        $revoked = $this->permissionService->revokePermission($agentId, $connection->id);

        $this->assertTrue($revoked);
        $this->assertDatabaseMissing('agent_database_permissions', [
            'agent_id' => $agentId,
            'database_connection_id' => $connection->id,
        ]);
    }

    /** @test */
    public function it_can_check_permission()
    {
        $connection = DatabaseConnection::create([
            'name' => 'Test Connection',
            'type' => DatabaseType::SQLITE,
            'database' => ':memory:',
            'status' => ConnectionStatus::ACTIVE,
        ]);

        $agentId = 1;

        // 授予READ_WRITE权限
        $this->permissionService->grantPermission(
            $agentId,
            $connection->id,
            PermissionLevel::READ_WRITE
        );

        $hasPermission = $this->permissionService->hasPermission(
            $agentId,
            $connection->id,
            PermissionLevel::READ_WRITE
        );

        $this->assertTrue($hasPermission);
    }

    /** @test */
    public function it_returns_false_for_no_permission()
    {
        $connection = DatabaseConnection::create([
            'name' => 'Test Connection',
            'type' => DatabaseType::SQLITE,
            'database' => ':memory:',
            'status' => ConnectionStatus::ACTIVE,
        ]);

        $agentId = 1;

        $hasPermission = $this->permissionService->hasPermission(
            $agentId,
            $connection->id,
            PermissionLevel::READ_WRITE
        );

        $this->assertFalse($hasPermission);
    }

    /** @test */
    public function it_can_check_permission_level_hierarchy()
    {
        $connection = DatabaseConnection::create([
            'name' => 'Test Connection',
            'type' => DatabaseType::SQLITE,
            'database' => ':memory:',
            'status' => ConnectionStatus::ACTIVE,
        ]);

        $agentId = 1;

        // 授予ADMIN权限
        $this->permissionService->grantPermission(
            $agentId,
            $connection->id,
            PermissionLevel::ADMIN
        );

        // ADMIN权限应该包含READ_WRITE和READ_ONLY
        $this->assertTrue($this->permissionService->hasPermission(
            $agentId,
            $connection->id,
            PermissionLevel::READ_WRITE
        ));

        $this->assertTrue($this->permissionService->hasPermission(
            $agentId,
            $connection->id,
            PermissionLevel::READ_ONLY
        ));
    }

    /** @test */
    public function it_can_get_agent_permissions()
    {
        $connection1 = DatabaseConnection::create([
            'name' => 'Connection 1',
            'type' => DatabaseType::SQLITE,
            'database' => ':memory:',
            'status' => ConnectionStatus::ACTIVE,
        ]);

        $connection2 = DatabaseConnection::create([
            'name' => 'Connection 2',
            'type' => DatabaseType::MYSQL,
            'host' => 'localhost',
            'port' => 3306,
            'database' => 'test_db',
            'username' => 'user',
            'password' => 'pass',
            'status' => ConnectionStatus::ACTIVE,
        ]);

        $agentId = 1;

        $this->permissionService->grantPermission(
            $agentId,
            $connection1->id,
            PermissionLevel::READ_WRITE
        );

        $this->permissionService->grantPermission(
            $agentId,
            $connection2->id,
            PermissionLevel::READ_ONLY
        );

        $permissions = $this->permissionService->getAgentPermissions($agentId);

        $this->assertCount(2, $permissions);
        $this->assertEquals($connection1->id, $permissions[0]->database_connection_id);
        $this->assertEquals($connection2->id, $permissions[1]->database_connection_id);
    }

    /** @test */
    public function it_can_get_connection_permissions()
    {
        $connection = DatabaseConnection::create([
            'name' => 'Test Connection',
            'type' => DatabaseType::SQLITE,
            'database' => ':memory:',
            'status' => ConnectionStatus::ACTIVE,
        ]);

        // 为多个agent授予权限
        $this->permissionService->grantPermission(1, $connection->id, PermissionLevel::ADMIN);
        $this->permissionService->grantPermission(2, $connection->id, PermissionLevel::READ_WRITE);
        $this->permissionService->grantPermission(3, $connection->id, PermissionLevel::READ_ONLY);

        $permissions = $this->permissionService->getConnectionPermissions($connection->id);

        $this->assertCount(3, $permissions);
    }

    /** @test */
    public function it_can_get_permission_level()
    {
        $connection = DatabaseConnection::create([
            'name' => 'Test Connection',
            'type' => DatabaseType::SQLITE,
            'database' => ':memory:',
            'status' => ConnectionStatus::ACTIVE,
        ]);

        $agentId = 1;

        $this->permissionService->grantPermission(
            $agentId,
            $connection->id,
            PermissionLevel::READ_WRITE
        );

        $level = $this->permissionService->getPermissionLevel($agentId, $connection->id);

        $this->assertEquals(PermissionLevel::READ_WRITE, $level);
    }

    /** @test */
    public function it_returns_null_for_nonexistent_permission_level()
    {
        $connection = DatabaseConnection::create([
            'name' => 'Test Connection',
            'type' => DatabaseType::SQLITE,
            'database' => ':memory:',
            'status' => ConnectionStatus::ACTIVE,
        ]);

        $level = $this->permissionService->getPermissionLevel(1, $connection->id);

        $this->assertNull($level);
    }

    /** @test */
    public function it_validates_permission_level()
    {
        $this->assertTrue($this->permissionService->isValidPermissionLevel(PermissionLevel::READ_ONLY));
        $this->assertTrue($this->permissionService->isValidPermissionLevel(PermissionLevel::READ_WRITE));
        $this->assertTrue($this->permissionService->isValidPermissionLevel(PermissionLevel::ADMIN));
        $this->assertFalse($this->permissionService->isValidPermissionLevel('INVALID'));
    }

    /** @test */
    public function it_can_bulk_grant_permissions()
    {
        $connection = DatabaseConnection::create([
            'name' => 'Test Connection',
            'type' => DatabaseType::SQLITE,
            'database' => ':memory:',
            'status' => ConnectionStatus::ACTIVE,
        ]);

        $agentIds = [1, 2, 3];
        $permissionLevel = PermissionLevel::READ_WRITE;

        $granted = $this->permissionService->bulkGrantPermissions(
            $agentIds,
            $connection->id,
            $permissionLevel
        );

        $this->assertTrue($granted);
        $this->assertCount(3, AgentDatabasePermission::where('database_connection_id', $connection->id)->get());
    }

    /** @test */
    public function it_can_bulk_revoke_permissions()
    {
        $connection = DatabaseConnection::create([
            'name' => 'Test Connection',
            'type' => DatabaseType::SQLITE,
            'database' => ':memory:',
            'status' => ConnectionStatus::ACTIVE,
        ]);

        $agentIds = [1, 2, 3];

        // 先授予权限
        $this->permissionService->bulkGrantPermissions(
            $agentIds,
            $connection->id,
            PermissionLevel::READ_WRITE
        );

        // 批量撤销权限
        $revoked = $this->permissionService->bulkRevokePermissions($agentIds, $connection->id);

        $this->assertTrue($revoked);
        $this->assertCount(0, AgentDatabasePermission::where('database_connection_id', $connection->id)->get());
    }
}