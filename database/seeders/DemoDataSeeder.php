<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Dbcont\Models\DatabaseConnection;
use Modules\MCP\Models\Agent;
use Modules\Dbcont\Enums\DatabaseType;
use Modules\Dbcont\Enums\ConnectionStatus;
use Modules\Dbcont\Models\AgentDatabasePermission;
use Modules\Dbcont\Enums\PermissionLevel;
use Modules\Project\Models\Project;
use Modules\User\Models\User;
use Illuminate\Support\Facades\Hash;

class DemoDataSeeder extends Seeder
{
    public function run()
    {
        // 防止重复执行
        if (DatabaseConnection::where('name', 'SQLite演示连接')->exists()) {
            $this->command->info('SQLite演示连接已存在，跳过创建');
            return;
        }

        // 创建用户2
        $user2 = User::firstOrCreate(
            ['email' => 'demo@example.com'],
            [
                'name' => 'Demo User',
                'username' => 'demouser',
                'email' => 'demo@example.com',
                'password' => Hash::make('demo123'),
                'email_verified_at' => now(),
                'timezone' => 'Asia/Shanghai',
            ]
        );

        $this->command->info("创建演示用户: {$user2->name} ({$user2->email})");

        // 为用户2创建演示项目
        $demoProject = Project::firstOrCreate(
            ['name' => 'Demo Project', 'user_id' => $user2->id],
            [
                'name' => 'Demo Project',
                'description' => '演示项目 - 用于测试多个Agent和权限配置',
                'user_id' => $user2->id,
                'status' => 'active',
                'repository_url' => null,
                'settings' => [
                    'auto_sync' => false,
                    'notifications' => true,
                ],
            ]
        );

        $this->command->info("创建演示项目: {$demoProject->name}");

        // 创建多个演示 Agent
        $agents = [
            [
                'identifier' => 'demo-agent-001',
                'name' => 'Demo Agent 1 - Read Only',
                'access_token' => 'demo001',
                'description' => '演示 Agent 1 - 只读权限',
                'capabilities' => [
                    'data_query' => true,
                    'report_generation' => true,
                ],
                'configuration' => [
                    'model' => 'claude-3.5',
                    'max_tokens' => 2000,
                ],
                'allowed_actions' => [
                    'read' => true,
                    'create' => false,
                    'update' => false,
                    'delete' => false,
                ],
            ],
            [
                'identifier' => 'demo-agent-002',
                'name' => 'Demo Agent 2 - Read Write',
                'access_token' => 'demo002',
                'description' => '演示 Agent 2 - 读写权限',
                'capabilities' => [
                    'data_management' => true,
                    'task_automation' => true,
                ],
                'configuration' => [
                    'model' => 'claude-3.5',
                    'max_tokens' => 4000,
                ],
                'allowed_actions' => [
                    'read' => true,
                    'create' => true,
                    'update' => true,
                    'delete' => false,
                ],
            ],
            [
                'identifier' => 'demo-agent-003',
                'name' => 'Demo Agent 3 - Full Access',
                'access_token' => 'demo003',
                'description' => '演示 Agent 3 - 完全权限',
                'capabilities' => [
                    'full_database_access' => true,
                    'system_administration' => true,
                ],
                'configuration' => [
                    'model' => 'claude-3.5',
                    'max_tokens' => 8000,
                ],
                'allowed_actions' => [
                    'read' => true,
                    'create' => true,
                    'update' => true,
                    'delete' => true,
                ],
            ],
        ];

        $createdAgents = [];
        foreach ($agents as $agentData) {
            $agent = Agent::firstOrCreate(
                ['identifier' => $agentData['identifier']],
                array_merge($agentData, [
                    'user_id' => $user2->id,
                    'project_id' => $demoProject->id,
                    'status' => 'active',
                    'last_active_at' => now(),
                ])
            );

            $createdAgents[] = $agent;
            $this->command->info("创建演示 Agent: {$agent->name} ({$agent->identifier})");
        }

        // 创建演示SQLite数据库连接
        $demoDb = DatabaseConnection::create([
            'project_id' => $demoProject->id,
            'name' => 'SQLite演示连接',
            'type' => DatabaseType::SQLITE,
            'database' => database_path('database.demo.sqlite'),
            'status' => ConnectionStatus::ACTIVE,
        ]);

        $this->command->info("创建演示数据库连接: {$demoDb->name}");

        // 为不同的Agent分配不同的权限
        $permissions = [
            'demo-agent-001' => [
                'permission_level' => PermissionLevel::READ_ONLY,
                'max_query_time' => 60,
                'max_result_rows' => 100,
            ],
            'demo-agent-002' => [
                'permission_level' => PermissionLevel::READ_WRITE,
                'max_query_time' => 300,
                'max_result_rows' => 1000,
            ],
            'demo-agent-003' => [
                'permission_level' => PermissionLevel::ADMIN,
                'max_query_time' => 600,
                'max_result_rows' => 5000,
            ],
        ];

        foreach ($createdAgents as $agent) {
            if (isset($permissions[$agent->identifier])) {
                $permConfig = $permissions[$agent->identifier];

                AgentDatabasePermission::create([
                    'agent_id' => $agent->id,
                    'database_connection_id' => $demoDb->id,
                    'permission_level' => $permConfig['permission_level'],
                    'max_query_time' => $permConfig['max_query_time'],
                    'max_result_rows' => $permConfig['max_result_rows'],
                ]);

                $this->command->info("为 Agent {$agent->name} ({$agent->identifier}) 分配权限: {$permConfig['permission_level']->value}");
            }
        }

        $this->command->info('演示数据库连接和权限配置完成！');
        $this->command->info('');
        $this->command->info('演示数据库信息:');
        $this->command->info("数据库文件: {$demoDb->database}");
        $this->command->info("连接名称: {$demoDb->name}");
        $this->command->info("演示用户: {$user2->name} ({$user2->email})");
        $this->command->info("演示项目: {$demoProject->name}");
        $this->command->info("创建的 Agent 数量: " . count($createdAgents));
    }
}
