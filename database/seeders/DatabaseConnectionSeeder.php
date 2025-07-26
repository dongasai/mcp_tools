<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Modules\Dbcont\Models\DatabaseConnection;
use App\Modules\MCP\Models\Agent;
use App\Modules\Dbcont\Enums\DatabaseType;
use App\Modules\Dbcont\Enums\ConnectionStatus;
use App\Modules\Dbcont\Models\AgentDatabasePermission;
use App\Modules\Dbcont\Enums\PermissionLevel;
use App\Modules\Project\Models\Project;

class DatabaseConnectionSeeder extends Seeder
{
    public function run()
    {
        // 防止重复执行
        if (DatabaseConnection::where('name', 'SQLite测试连接')->exists()) {
            return;
        }

        // 获取第一个项目
        $project = Project::first();
        if (!$project) {
            $this->command->error('没有找到项目，请先运行 McpTestDataSeeder');
            return;
        }

        // 创建默认SQLite数据库连接
        $db = DatabaseConnection::create([
            'project_id' => $project->id,
            'name' => 'SQLite测试连接',
            'type' => DatabaseType::SQLITE,
            'database' => database_path('database.sqlite'),
            'status' => ConnectionStatus::ACTIVE,
        ]);

        // 获取第一个Agent并授予权限
        if ($agent = Agent::first()) {
            AgentDatabasePermission::create([
                'agent_id' => $agent->id,
                'database_connection_id' => $db->id,
                'permission_level' => PermissionLevel::READ_WRITE,
                'max_query_time' => 300,
                'max_result_rows' => 1000,
            ]);
        }
    }
}