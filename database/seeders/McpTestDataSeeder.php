<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Modules\User\Models\User;
use App\Modules\Project\Models\Project;
use App\Modules\Agent\Models\Agent;
use Illuminate\Support\Facades\Hash;

class McpTestDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 创建默认用户
        $user = User::firstOrCreate(
            ['email' => 'test@example.com'],
            [
                'name' => 'Test User',
                'username' => 'testuser',
                'email' => 'test@example.com',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
                'status' => User::STATUS_ACTIVE,
                'role' => User::ROLE_USER,
            ]
        );

        $this->command->info("创建默认用户: {$user->name} ({$user->email})");

        // 创建默认项目
        $project = Project::firstOrCreate(
            ['name' => 'Default Project', 'user_id' => $user->id],
            [
                'name' => 'Default Project',
                'description' => 'MCP 测试默认项目',
                'user_id' => $user->id,
                'status' => 'active',
                'repository_url' => 'https://github.com/example/default-project',
                'settings' => [
                    'auto_sync' => true,
                    'notifications' => true,
                ],
            ]
        );

        $this->command->info("创建默认项目: {$project->name}");

        // 创建默认 Agent
        $agent = Agent::firstOrCreate(
            ['identifier' => 'test-agent-001'],
            [
                'identifier' => 'test-agent-001',
                'name' => 'Test Agent',
                'user_id' => $user->id,
                'project_id' => $project->id,
                'access_token' => '123456',
                'status' => 'active',
                'description' => 'MCP 测试默认 Agent',
                'capabilities' => [
                    'task_management' => true,
                    'project_query' => true,
                    'resource_access' => true,
                ],
                'configuration' => [
                    'model' => 'claude-3.5',
                    'max_tokens' => 4000,
                ],
                'allowed_actions' => [
                    'read' => true,
                    'create_task' => true,
                    'update_task' => true,
                    'delete_task' => false,
                ],
                'last_active_at' => now(),
            ]
        );

        $this->command->info("创建默认 Agent: {$agent->name} (Token: {$agent->access_token})");

        $this->command->info('MCP 测试数据创建完成！');
        $this->command->info('');
        $this->command->info('测试信息:');
        $this->command->info("用户: {$user->name} ({$user->email})");
        $this->command->info("项目: {$project->name}");
        $this->command->info("Agent: {$agent->name} ({$agent->identifier})");
        $this->command->info("Token: {$agent->access_token}");
    }
}
