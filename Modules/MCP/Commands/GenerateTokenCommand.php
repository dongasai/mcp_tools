<?php

namespace Modules\MCP\Commands;

use Illuminate\Console\Command;
use Modules\MCP\Models\Agent;
use Modules\MCP\Services\AuthenticationService;
use Modules\User\Models\User;

class GenerateTokenCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'agent:generate-token 
                            {agent_id : The Agent ID to generate token for}
                            {--refresh : Refresh existing token}
                            {--show-info : Show detailed agent information}';

    /**
     * The console command description.
     */
    protected $description = 'Generate or refresh access token for an Agent';

    public function __construct(
        private AuthenticationService $authService
    ) {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $agentId = $this->argument('agent_id');
        $refresh = $this->option('refresh');
        $showInfo = $this->option('show-info');

        try {
            // 查找Agent
            $agent = Agent::where('identifier', $agentId)->first();
            
            if (!$agent) {
                $this->error("Agent with ID '{$agentId}' not found.");
                return 1;
            }

            // 显示Agent信息
            if ($showInfo) {
                $this->displayAgentInfo($agent);
            }

            // 检查是否需要刷新令牌
            if ($agent->access_token && !$refresh) {
                if (!$agent->isTokenExpired()) {
                    $this->warn("Agent already has a valid token. Use --refresh to generate a new one.");
                    $this->line("Current token: " . substr($agent->access_token, 0, 20) . "...");
                    $this->line("Expires at: " . $agent->token_expires_at);
                    return 0;
                }
            }

            // 生成或刷新令牌
            if ($refresh || $agent->isTokenExpired()) {
                $token = $this->authService->refreshToken($agent);
                $action = $refresh ? 'refreshed' : 'generated';
            } else {
                $token = $this->authService->generateTokenForAgent($agent);
                $action = 'generated';
            }

            // 显示结果
            $this->info("Token {$action} successfully for Agent '{$agentId}'");
            $this->line("Token: {$token}");
            $this->line("Expires at: {$agent->fresh()->token_expires_at}");

            // 显示使用示例
            $this->newLine();
            $this->comment("Usage examples:");
            $this->line("curl -H 'X-Agent-Token: {$token}' -H 'X-Agent-ID: {$agentId}' http://localhost:34004/api/tasks/mcp-test/mcp-info");
            $this->line("curl -H 'Authorization: Bearer {$token}' -H 'X-Agent-ID: {$agentId}' http://localhost:34004/api/tasks/mcp-test/create-main-task");

            return 0;

        } catch (\Exception $e) {
            $this->error("Error generating token: " . $e->getMessage());
            return 1;
        }
    }

    /**
     * 显示Agent详细信息
     */
    private function displayAgentInfo(Agent $agent): void
    {
        $this->info("Agent Information:");
        $this->table(
            ['Property', 'Value'],
            [
                ['ID', $agent->id],
                ['Agent ID', $agent->identifier],
                ['Name', $agent->name],
                ['Status', $agent->status],
                ['User ID', $agent->user_id],
                ['User Name', $agent->user->name ?? 'N/A'],
                ['Created At', $agent->created_at],
                ['Last Active', $agent->last_active_at ?? 'Never'],
                ['Token Expires', $agent->token_expires_at ?? 'No token'],
                ['Token Expired', $agent->isTokenExpired() ? 'Yes' : 'No'],
                ['Allowed Projects', implode(', ', $agent->allowed_projects ?? [])],
                ['Allowed Actions', implode(', ', $agent->allowed_actions ?? [])],
            ]
        );
        $this->newLine();
    }
}
