<?php

namespace App\Console\Commands;

use App\Models\Agent;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class McpAgentRegister extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mcp:agent:register
                            {--name= : Agent name}
                            {--type= : Agent type (claude-3.5-sonnet, gpt-4, etc.)}
                            {--user-id= : User ID that owns this agent}
                            {--projects= : Comma-separated list of project IDs}
                            {--permissions= : Comma-separated list of permissions}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Register a new MCP Agent';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $name = $this->option('name') ?: $this->ask('Agent name');
        $type = $this->option('type') ?: $this->ask('Agent type (e.g., claude-3.5-sonnet)');
        $userId = $this->option('user-id') ?: $this->ask('User ID');

        // Validate user exists
        $user = User::find($userId);
        if (!$user) {
            $this->error("User with ID {$userId} not found.");
            return 1;
        }

        $projectsInput = $this->option('projects') ?: $this->ask('Project IDs (comma-separated)', '');
        $permissionsInput = $this->option('permissions') ?: $this->ask('Permissions (comma-separated)', 'read,create_task,update_task');

        $projects = $projectsInput ? array_map('intval', explode(',', $projectsInput)) : [];
        $permissions = $permissionsInput ? explode(',', $permissionsInput) : ['read'];

        // Generate unique agent ID
        $agentId = 'agent_' . str_pad(Agent::count() + 1, 3, '0', STR_PAD_LEFT) . '_' . Str::slug($name);

        // Generate access token
        $accessToken = 'mcp_token_' . Str::random(40);

        $agent = Agent::create([
            'agent_id' => $agentId,
            'name' => $name,
            'type' => $type,
            'access_token' => $accessToken,
            'allowed_projects' => $projects,
            'allowed_actions' => $permissions,
            'status' => 'active',
            'token_expires_at' => now()->addSeconds((int) config('mcp.access_control.token_expiry', 86400)),
            'user_id' => $userId,
        ]);

        $this->info('Agent registered successfully!');
        $this->line('');
        $this->line("Agent ID: {$agent->agent_id}");
        $this->line("Access Token: {$accessToken}");
        $this->line("Allowed Projects: " . implode(', ', $projects));
        $this->line("Permissions: " . implode(', ', $permissions));
        $this->line('');
        $this->warn('Please save the access token securely. It will not be shown again.');

        return 0;
    }
}
