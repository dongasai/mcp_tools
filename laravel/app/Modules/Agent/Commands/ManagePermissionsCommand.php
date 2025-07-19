<?php

namespace App\Modules\Agent\Commands;

use Illuminate\Console\Command;
use App\Modules\Agent\Models\Agent;
use App\Modules\Agent\Services\AuthorizationService;
use App\Modules\Project\Models\Project;

class ManagePermissionsCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'agent:permissions 
                            {agent_id : The Agent ID to manage permissions for}
                            {action : Action to perform: grant-project, revoke-project, grant-action, revoke-action, list}
                            {value? : Project ID or action name}';

    /**
     * The console command description.
     */
    protected $description = 'Manage Agent permissions for projects and actions';

    public function __construct(
        private AuthorizationService $authzService
    ) {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $agentId = $this->argument('agent_id');
        $action = $this->argument('action');
        $value = $this->argument('value');

        try {
            // 查找Agent
            $agent = Agent::where('identifier', $agentId)->first();
            
            if (!$agent) {
                $this->error("Agent with ID '{$agentId}' not found.");
                return 1;
            }

            return match ($action) {
                'grant-project' => $this->grantProjectAccess($agent, $value),
                'revoke-project' => $this->revokeProjectAccess($agent, $value),
                'grant-action' => $this->grantAction($agent, $value),
                'revoke-action' => $this->revokeAction($agent, $value),
                'list' => $this->listPermissions($agent),
                default => $this->invalidAction($action)
            };

        } catch (\Exception $e) {
            $this->error("Error managing permissions: " . $e->getMessage());
            return 1;
        }
    }

    /**
     * 授予项目访问权限
     */
    private function grantProjectAccess(Agent $agent, ?string $projectId): int
    {
        if (!$projectId) {
            $this->error("Project ID is required for grant-project action.");
            return 1;
        }

        $project = Project::find($projectId);
        if (!$project) {
            $this->error("Project with ID '{$projectId}' not found.");
            return 1;
        }

        if ($this->authzService->grantProjectAccess($agent, (int)$projectId)) {
            $this->info("Granted access to project '{$project->name}' (ID: {$projectId}) for Agent '{$agent->identifier}'");
            return 0;
        } else {
            $this->error("Failed to grant project access.");
            return 1;
        }
    }

    /**
     * 撤销项目访问权限
     */
    private function revokeProjectAccess(Agent $agent, ?string $projectId): int
    {
        if (!$projectId) {
            $this->error("Project ID is required for revoke-project action.");
            return 1;
        }

        if ($this->authzService->revokeProjectAccess($agent, (int)$projectId)) {
            $this->info("Revoked access to project ID {$projectId} for Agent '{$agent->identifier}'");
            return 0;
        } else {
            $this->error("Failed to revoke project access.");
            return 1;
        }
    }

    /**
     * 授予操作权限
     */
    private function grantAction(Agent $agent, ?string $action): int
    {
        if (!$action) {
            $this->error("Action name is required for grant-action.");
            return 1;
        }

        if ($this->authzService->grantAction($agent, $action)) {
            $this->info("Granted action '{$action}' for Agent '{$agent->identifier}'");
            return 0;
        } else {
            $this->error("Failed to grant action permission.");
            return 1;
        }
    }

    /**
     * 撤销操作权限
     */
    private function revokeAction(Agent $agent, ?string $action): int
    {
        if (!$action) {
            $this->error("Action name is required for revoke-action.");
            return 1;
        }

        if ($this->authzService->revokeAction($agent, $action)) {
            $this->info("Revoked action '{$action}' for Agent '{$agent->identifier}'");
            return 0;
        } else {
            $this->error("Failed to revoke action permission.");
            return 1;
        }
    }

    /**
     * 列出权限
     */
    private function listPermissions(Agent $agent): int
    {
        $this->info("Permissions for Agent '{$agent->identifier}':");
        
        // 显示项目权限
        $projects = $this->authzService->getAccessibleProjects($agent);
        if (!empty($projects)) {
            $this->newLine();
            $this->comment("Accessible Projects:");
            $this->table(
                ['ID', 'Name', 'Status'],
                array_map(fn($p) => [$p['id'], $p['name'], $p['status']], $projects)
            );
        } else {
            $this->newLine();
            $this->comment("No project access granted.");
        }

        // 显示操作权限
        $actions = $this->authzService->getAllowedActions($agent);
        if (!empty($actions)) {
            $this->newLine();
            $this->comment("Allowed Actions:");
            foreach ($actions as $action) {
                $this->line("  - {$action}");
            }
        } else {
            $this->newLine();
            $this->comment("No action permissions granted.");
        }

        // 显示常用操作示例
        $this->newLine();
        $this->comment("Common actions to grant:");
        $commonActions = [
            'create_task' => 'Create new tasks',
            'update_task' => 'Update existing tasks',
            'complete_task' => 'Mark tasks as completed',
            'add_comment' => 'Add comments to tasks',
            'read_task' => 'Read task information',
            'list_tasks' => 'List tasks'
        ];

        foreach ($commonActions as $action => $description) {
            $hasPermission = in_array($action, $actions) ? '✓' : '✗';
            $this->line("  {$hasPermission} {$action} - {$description}");
        }

        return 0;
    }

    /**
     * 处理无效操作
     */
    private function invalidAction(string $action): int
    {
        $this->error("Invalid action '{$action}'.");
        $this->comment("Available actions: grant-project, revoke-project, grant-action, revoke-action, list");
        return 1;
    }
}
