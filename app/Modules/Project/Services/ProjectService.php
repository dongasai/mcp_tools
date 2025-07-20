<?php

namespace App\Modules\Project\Services;

use App\Modules\Project\Models\Project;
use App\Modules\User\Models\User;
use App\Modules\Agent\Models\Agent;
use App\Modules\Core\Contracts\LogInterface;
use App\Modules\Core\Contracts\EventInterface;
use App\Modules\Core\Validators\SimpleValidator;
use Illuminate\Support\Collection;

class ProjectService
{
    protected LogInterface $logger;
    protected EventInterface $eventDispatcher;

    public function __construct(
        LogInterface $logger,
        EventInterface $eventDispatcher
    ) {
        $this->logger = $logger;
        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * 创建项目
     */
    public function create(User $user, array $data): Project
    {
        // 验证数据
        $validatedData = SimpleValidator::check($data, [
            'name' => 'required|string|min:2|max:255',
            'description' => 'string|max:1000',
            'repository_url' => 'url',
            'branch' => 'string|max:100',
            'agent_id' => 'integer|exists:agents,id',
            'priority' => 'string|in:low,medium,high,urgent',
            'settings' => 'array',
        ]);

        if (empty($validatedData)) {
            $validator = SimpleValidator::make($data, [
                'name' => 'required|string|min:2|max:255',
                'description' => 'string|max:1000',
                'repository_url' => 'url',
                'branch' => 'string|max:100',
                'agent_id' => 'integer',
                'priority' => 'string|in:low,medium,high,urgent',
                'settings' => 'array',
            ]);
            throw new \InvalidArgumentException('Validation failed: ' . $validator->getFirstError());
        }

        // 验证Agent权限
        if (isset($validatedData['agent_id'])) {
            $agent = Agent::find($validatedData['agent_id']);
            if (!$agent || $agent->user_id !== $user->id) {
                throw new \InvalidArgumentException('Invalid agent or insufficient permissions');
            }
        }

        // 创建项目
        $project = Project::create([
            'user_id' => $user->id,
            'name' => $validatedData['name'],
            'description' => $validatedData['description'] ?? null,
            'repository_url' => $validatedData['repository_url'] ?? null,
            'branch' => $validatedData['branch'] ?? 'main',
            'agent_id' => $validatedData['agent_id'] ?? null,
            'priority' => $validatedData['priority'] ?? Project::PRIORITY_MEDIUM,
            'status' => Project::STATUS_ACTIVE,
            'settings' => $validatedData['settings'] ?? [],
            'metadata' => [],
        ]);

        // 记录日志
        $this->logger->audit('project_created', $user->id, [
            'project_id' => $project->id,
            'name' => $project->name,
            'agent_id' => $project->agent_id,
        ]);

        // 分发事件
        $this->eventDispatcher->dispatch(new \App\Modules\Project\Events\ProjectCreated($project));

        return $project;
    }

    /**
     * 更新项目
     */
    public function update(Project $project, array $data): Project
    {
        // 验证数据
        $validatedData = SimpleValidator::check($data, [
            'name' => 'string|min:2|max:255',
            'description' => 'string|max:1000',
            'repository_url' => 'url',
            'branch' => 'string|max:100',
            'agent_id' => 'integer',
            'priority' => 'string|in:low,medium,high,urgent',
            'status' => 'string|in:active,inactive,completed,archived,suspended',
            'settings' => 'array',
        ]);

        if (empty($validatedData)) {
            $validator = SimpleValidator::make($data, [
                'name' => 'string|min:2|max:255',
                'description' => 'string|max:1000',
                'repository_url' => 'url',
                'branch' => 'string|max:100',
                'agent_id' => 'integer',
                'priority' => 'string|in:low,medium,high,urgent',
                'status' => 'string|in:active,inactive,completed,archived,suspended',
                'settings' => 'array',
            ]);
            throw new \InvalidArgumentException('验证失败: ' . $validator->getFirstError());
        }

        // 验证Agent权限
        if (isset($validatedData['agent_id'])) {
            $agent = Agent::find($validatedData['agent_id']);
            if (!$agent || $agent->user_id !== $project->user_id) {
                throw new \InvalidArgumentException('无效的Agent或权限不足');
            }
        }

        // 记录原始状态
        $originalStatus = $project->status;
        $originalAgentId = $project->agent_id;

        // 更新项目
        $project->update($validatedData);

        // 如果状态发生变化，记录日志和分发事件
        if (isset($validatedData['status']) && $originalStatus !== $validatedData['status']) {
            $this->logger->audit('project_status_changed', $project->user_id, [
                'project_id' => $project->id,
                'old_status' => $originalStatus,
                'new_status' => $validatedData['status'],
            ]);

            $this->eventDispatcher->dispatch(new \App\Modules\Project\Events\ProjectStatusChanged($project, $originalStatus));
        }

        // 如果Agent发生变化，记录日志和分发事件
        if (isset($validatedData['agent_id']) && $originalAgentId !== $validatedData['agent_id']) {
            $this->logger->audit('project_agent_changed', $project->user_id, [
                'project_id' => $project->id,
                'old_agent_id' => $originalAgentId,
                'new_agent_id' => $validatedData['agent_id'],
            ]);

            $this->eventDispatcher->dispatch(new \App\Modules\Project\Events\ProjectAgentChanged($project, $originalAgentId));
        }

        // 记录更新日志
        $this->logger->audit('project_updated', $project->user_id, [
            'project_id' => $project->id,
            'updated_fields' => array_keys($validatedData),
        ]);

        return $project->fresh();
    }

    /**
     * 删除项目
     */
    public function delete(Project $project): bool
    {
        // 检查是否有活跃的任务
        $activeTasks = $project->tasks()->whereIn('status', ['pending', 'in_progress'])->count();
        if ($activeTasks > 0) {
            throw new \InvalidArgumentException('无法删除包含活跃任务的项目');
        }

        // 记录日志
        $this->logger->audit('project_deleted', $project->user_id, [
            'project_id' => $project->id,
            'name' => $project->name,
        ]);

        // 分发事件
        $this->eventDispatcher->dispatch(new \App\Modules\Project\Events\ProjectDeleted($project));

        // 删除项目
        return $project->delete();
    }

    /**
     * 获取用户的项目列表
     */
    public function getUserProjects(User $user, array $filters = []): Collection
    {
        $query = Project::byUser($user->id)->with(['user', 'agent']);

        // 应用过滤器
        if (isset($filters['status'])) {
            $query->byStatus($filters['status']);
        }

        if (isset($filters['priority'])) {
            $query->byPriority($filters['priority']);
        }

        if (isset($filters['agent_id'])) {
            $query->byAgent($filters['agent_id']);
        }

        if (isset($filters['search'])) {
            $query->search($filters['search']);
        }

        return $query->orderBy('created_at', 'desc')->get();
    }

    /**
     * 获取Agent的项目列表
     */
    public function getAgentProjects(Agent $agent, array $filters = []): Collection
    {
        $query = Project::byAgent($agent->id)->with(['user', 'agent']);

        // 应用过滤器
        if (isset($filters['status'])) {
            $query->byStatus($filters['status']);
        }

        if (isset($filters['priority'])) {
            $query->byPriority($filters['priority']);
        }

        return $query->orderBy('created_at', 'desc')->get();
    }

    /**
     * 获取项目统计信息
     */
    public function getProjectStats(Project $project): array
    {
        return $project->getStats();
    }

    /**
     * 获取系统项目统计信息
     */
    public function getSystemStats(): array
    {
        try {
            return [
                'total_projects' => Project::count(),
                'active_projects' => Project::active()->count(),
                'completed_projects' => Project::byStatus(Project::STATUS_COMPLETED)->count(),
                'archived_projects' => Project::byStatus(Project::STATUS_ARCHIVED)->count(),
                'suspended_projects' => Project::byStatus(Project::STATUS_SUSPENDED)->count(),
                'projects_by_priority' => [
                    'low' => Project::byPriority(Project::PRIORITY_LOW)->count(),
                    'medium' => Project::byPriority(Project::PRIORITY_MEDIUM)->count(),
                    'high' => Project::byPriority(Project::PRIORITY_HIGH)->count(),
                    'urgent' => Project::byPriority(Project::PRIORITY_URGENT)->count(),
                ],
                'table_exists' => true,
            ];
        } catch (\Exception $e) {
            return [
                'total_projects' => 0,
                'active_projects' => 0,
                'completed_projects' => 0,
                'archived_projects' => 0,
                'suspended_projects' => 0,
                'projects_by_priority' => [
                    'low' => 0,
                    'medium' => 0,
                    'high' => 0,
                    'urgent' => 0,
                ],
                'table_exists' => false,
                'error' => $e->getMessage(),
            ];
        }
    }
}
