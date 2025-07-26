<?php

namespace Modules\MCP\Services;

use Modules\MCP\Models\Agent;
use Modules\Project\Models\Project;
use Modules\Task\Models\Task;
use App\Modules\Core\Services\LogService;
use Illuminate\Support\Facades\Cache;

class AuthorizationService
{
    public function __construct(
        private LogService $logger
    ) {}

    /**
     * 检查Agent是否有权限访问项目
     */
    public function canAccessProject(Agent $agent, int $projectId): bool
    {
        try {
            // 检查Agent状态
            if ($agent->status !== Agent::STATUS_ACTIVE) {
                return false;
            }

            // 检查项目是否存在
            $project = Project::find($projectId);
            if (!$project) {
                $this->logger->warning('Project not found for access check', [
                    'agent_id' => $agent->agent_id,
                    'project_id' => $projectId
                ]);
                return false;
            }

            // 检查项目状态
            if ($project->status !== 'active') {
                $this->logger->info('Project is not active', [
                    'agent_id' => $agent->agent_id,
                    'project_id' => $projectId,
                    'project_status' => $project->status
                ]);
                return false;
            }

            // 检查Agent是否有项目访问权限（强绑定模式）
            if ($agent->project_id !== $projectId) {
                $this->logger->warning('Agent does not have access to project', [
                    'agent_id' => $agent->agent_id,
                    'project_id' => $projectId,
                    'agent_project_id' => $agent->project_id
                ]);
                return false;
            }

            return true;

        } catch (\Exception $e) {
            $this->logger->error('Error checking project access', [
                'agent_id' => $agent->agent_id,
                'project_id' => $projectId,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * 检查Agent是否有权限执行特定操作
     */
    public function canPerformAction(Agent $agent, string $action): bool
    {
        try {
            // 检查Agent状态
            if ($agent->status !== Agent::STATUS_ACTIVE) {
                return false;
            }

            // 检查Agent是否有该操作权限
            $allowedActions = $agent->allowed_actions ?? [];
            $hasPermission = in_array($action, $allowedActions);

            if (!$hasPermission) {
                $this->logger->info('Agent does not have permission for action', [
                    'agent_id' => $agent->agent_id,
                    'action' => $action,
                    'allowed_actions' => $allowedActions
                ]);
            }

            return $hasPermission;

        } catch (\Exception $e) {
            $this->logger->error('Error checking action permission', [
                'agent_id' => $agent->agent_id,
                'action' => $action,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * 检查Agent是否有权限访问任务
     */
    public function canAccessTask(Agent $agent, int $taskId): bool
    {
        try {
            // 查找任务
            $task = Task::with('project')->find($taskId);
            if (!$task) {
                $this->logger->warning('Task not found for access check', [
                    'agent_id' => $agent->agent_id,
                    'task_id' => $taskId
                ]);
                return false;
            }

            // 检查是否有项目访问权限
            if ($task->project_id && !$this->canAccessProject($agent, $task->project_id)) {
                return false;
            }

            // 检查任务是否分配给该Agent
            if ($task->agent_id && $task->agent_id !== $agent->agent_id) {
                $this->logger->info('Task is assigned to different agent', [
                    'agent_id' => $agent->agent_id,
                    'task_id' => $taskId,
                    'assigned_agent' => $task->agent_id
                ]);
                return false;
            }

            return true;

        } catch (\Exception $e) {
            $this->logger->error('Error checking task access', [
                'agent_id' => $agent->agent_id,
                'task_id' => $taskId,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * 检查Agent是否有权限在项目中执行操作
     */
    public function canPerformProjectAction(Agent $agent, int $projectId, string $action): bool
    {
        // 首先检查项目访问权限
        if (!$this->canAccessProject($agent, $projectId)) {
            return false;
        }

        // 然后检查操作权限
        return $this->canPerformAction($agent, $action);
    }

    /**
     * 检查Agent是否有权限对任务执行操作
     */
    public function canPerformTaskAction(Agent $agent, int $taskId, string $action): bool
    {
        // 首先检查任务访问权限
        if (!$this->canAccessTask($agent, $taskId)) {
            return false;
        }

        // 然后检查操作权限
        return $this->canPerformAction($agent, $action);
    }

    /**
     * 获取Agent可访问的项目列表
     */
    public function getAccessibleProjects(Agent $agent): array
    {
        $cacheKey = "agent_projects:{$agent->id}";

        return Cache::remember($cacheKey, 300, function () use ($agent) {
            if (!$agent->project_id) {
                return [];
            }

            $project = Project::where('id', $agent->project_id)
                ->where('status', 'active')
                ->select('id', 'name', 'status')
                ->first();

            return $project ? [$project->toArray()] : [];
        });
    }

    /**
     * 获取Agent可执行的操作列表
     */
    public function getAllowedActions(Agent $agent): array
    {
        return $agent->allowed_actions ?? [];
    }

    /**
     * 为Agent授予项目访问权限（设置Agent的主项目）
     */
    public function grantProjectAccess(Agent $agent, int $projectId): bool
    {
        try {
            // 在新的一对多关系中，直接设置Agent的project_id
            $agent->project_id = $projectId;
            $agent->save();

            // 清除缓存
            Cache::forget("agent_projects:{$agent->id}");

            $this->logger->audit('agent_project_access_granted', $agent->user_id, [
                'agent_id' => $agent->agent_id,
                'project_id' => $projectId
            ]);

            return true;

        } catch (\Exception $e) {
            $this->logger->error('Failed to grant project access', [
                'agent_id' => $agent->agent_id,
                'project_id' => $projectId,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * 撤销Agent的项目访问权限（移除Agent的主项目）
     */
    public function revokeProjectAccess(Agent $agent, int $projectId): bool
    {
        try {
            // 在新的一对多关系中，只有当前项目匹配时才撤销
            if ($agent->project_id === $projectId) {
                $agent->project_id = null;
                $agent->save();

                // 清除缓存
                Cache::forget("agent_projects:{$agent->id}");

                $this->logger->audit('agent_project_access_revoked', $agent->user_id, [
                    'agent_id' => $agent->agent_id,
                    'project_id' => $projectId
                ]);
            }

            return true;

        } catch (\Exception $e) {
            $this->logger->error('Failed to revoke project access', [
                'agent_id' => $agent->agent_id,
                'project_id' => $projectId,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * 为Agent授予操作权限
     */
    public function grantAction(Agent $agent, string $action): bool
    {
        try {
            $allowedActions = $agent->allowed_actions ?? [];
            
            if (!in_array($action, $allowedActions)) {
                $allowedActions[] = $action;
                $agent->allowed_actions = $allowedActions;
                $agent->save();

                $this->logger->audit('agent_action_granted', $agent->user_id, [
                    'agent_id' => $agent->agent_id,
                    'action' => $action
                ]);
            }

            return true;

        } catch (\Exception $e) {
            $this->logger->error('Failed to grant action permission', [
                'agent_id' => $agent->agent_id,
                'action' => $action,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * 撤销Agent的操作权限
     */
    public function revokeAction(Agent $agent, string $action): bool
    {
        try {
            $allowedActions = $agent->allowed_actions ?? [];
            $allowedActions = array_filter($allowedActions, fn($act) => $act !== $action);
            
            $agent->allowed_actions = array_values($allowedActions);
            $agent->save();

            $this->logger->audit('agent_action_revoked', $agent->user_id, [
                'agent_id' => $agent->agent_id,
                'action' => $action
            ]);

            return true;

        } catch (\Exception $e) {
            $this->logger->error('Failed to revoke action permission', [
                'agent_id' => $agent->agent_id,
                'action' => $action,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * 验证Agent权限的完整性检查
     */
    public function validateAgentPermissions(Agent $agent, array $context = []): array
    {
        $issues = [];

        // 检查基本状态
        if ($agent->status !== Agent::STATUS_ACTIVE) {
            $issues[] = "Agent status is not active: {$agent->status}";
        }

        // 检查令牌状态
        if ($agent->isTokenExpired()) {
            $issues[] = "Agent access token has expired";
        }

        // 检查项目权限
        $allowedProjects = $agent->allowed_projects ?? [];
        foreach ($allowedProjects as $projectId) {
            $project = Project::find($projectId);
            if (!$project) {
                $issues[] = "Referenced project {$projectId} does not exist";
            } elseif ($project->status !== 'active') {
                $issues[] = "Referenced project {$projectId} is not active";
            }
        }

        return $issues;
    }
}
