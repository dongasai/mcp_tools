<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Project;
use App\Models\Task;
use App\Models\Agent;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Seed admin tables first
        $this->call(AdminTablesSeeder::class);

        // Create a test user
        $user = User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'username' => 'testuser',
            'status' => 'active',
        ]);

        // Create a test project
        $project = Project::create([
            'name' => 'AI助手开发',
            'description' => '开发一个智能客服AI助手',
            'timezone' => 'Asia/Shanghai',
            'status' => 'active',
            'repositories' => ['https://github.com/example/ai-assistant'],
            'user_id' => $user->id,
        ]);

        // Create a test agent
        $agent = Agent::create([
            'agent_id' => 'agent_001_claude_dev',
            'name' => 'Claude开发助手',
            'type' => 'claude-3.5-sonnet',
            'access_token' => 'mcp_token_' . \Illuminate\Support\Str::random(40),
            'project_id' => $project->id,
            'allowed_actions' => ['read', 'create_task', 'update_task', 'claim_task'],
            'status' => 'active',
            'token_expires_at' => now()->addDay(),
            'user_id' => $user->id,
        ]);

        // Create some test tasks
        Task::create([
            'title' => '实现用户认证功能',
            'description' => '用户无法通过GitHub OAuth登录',
            'status' => 'pending',
            'priority' => 'high',
            'labels' => ['bug', 'authentication'],
            'project_id' => $project->id,
        ]);

        Task::create([
            'title' => '优化数据库查询性能',
            'description' => '某些查询响应时间过长，需要优化',
            'status' => 'pending',
            'priority' => 'medium',
            'labels' => ['performance', 'database'],
            'project_id' => $project->id,
        ]);

        Task::create([
            'title' => '添加API文档',
            'description' => '为所有API端点添加详细的文档',
            'status' => 'claimed',
            'priority' => 'low',
            'labels' => ['documentation'],
            'project_id' => $project->id,
            'agent_id' => $agent->agent_id,
        ]);
    }
}
