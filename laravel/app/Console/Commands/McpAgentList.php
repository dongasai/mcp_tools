<?php

namespace App\Console\Commands;

use App\Models\Agent;
use Illuminate\Console\Command;

class McpAgentList extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mcp:agent:list {--online : Show only online agents}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'List all MCP Agents';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $query = Agent::with('user');

        if ($this->option('online')) {
            $query->online();
            $this->info('Online MCP Agents:');
        } else {
            $this->info('All MCP Agents:');
        }

        $agents = $query->get();

        if ($agents->isEmpty()) {
            $this->warn('No agents found.');
            return 0;
        }

        $headers = ['Agent ID', 'Name', 'Type', 'Status', 'User', 'Projects', 'Last Active'];
        $rows = [];

        foreach ($agents as $agent) {
            $rows[] = [
                $agent->agent_id,
                $agent->name,
                $agent->type ?: 'N/A',
                $agent->status,
                $agent->user->name,
                implode(', ', $agent->allowed_projects ?: []),
                $agent->last_active_at ? $agent->last_active_at->diffForHumans() : 'Never',
            ];
        }

        $this->table($headers, $rows);

        return 0;
    }
}
