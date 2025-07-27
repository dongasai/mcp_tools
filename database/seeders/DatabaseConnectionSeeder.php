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

class DatabaseConnectionSeeder extends Seeder
{
    public function run()
    {
        // 获取测试用户
        $user = User::where('email', 'test@example.com')->first();
        if (!$user) {
            $this->command->error('没有找到测试用户，请先运行 MCPTestDataSeeder');
            return;
        }

        // 获取默认项目
        $project = Project::where('name', 'Default Project')
                          ->where('user_id', $user->id)
                          ->first();
        if (!$project) {
            $this->command->error('没有找到默认项目，请先运行 MCPTestDataSeeder');
            return;
        }

        // 创建默认SQLite数据库连接
        $db1 = DatabaseConnection::create([
            'project_id' => $project->id,
            'name' => 'SQLite测试连接',
            'type' => DatabaseType::SQLITE,
            'database' => database_path('database.sqlite'),
            'status' => ConnectionStatus::ACTIVE,
        ]);

        $this->command->info("创建数据库连接: {$db1->name}");

        // 给指定的Agent分配主数据库权限
        $agent1 = Agent::where('identifier', 'test-agent-001')->first();
        if ($agent1) {
            AgentDatabasePermission::create([
                'agent_id' => $agent1->id,
                'database_connection_id' => $db1->id,
                'permission_level' => PermissionLevel::READ_WRITE,
                'max_query_time' => 300,
                'max_result_rows' => 1000,
            ]);

            $this->command->info("为 Agent {$agent1->name} ({$agent1->identifier}) 分配主数据库权限");
        } else {
            $this->command->warn('没有找到 Agent test-agent-001，无法分配主数据库权限');
        }

        $this->command->info('主数据库连接和权限配置完成！');
    }
}