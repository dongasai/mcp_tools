<?php

namespace Modules\MCP\Services;

use Modules\MCP\Models\Agent;
use Modules\User\Models\User;
use App\Modules\Core\Contracts\LogInterface;
use App\Modules\Core\Contracts\EventInterface;
use App\Modules\Core\Validators\SimpleValidator;
use Illuminate\Support\Str;
use Illuminate\Support\Collection;

class AgentService
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
     * 创建Agent
     */
    public function create(User $user, array $data): Agent
    {
        // 验证数据
        $validatedData = SimpleValidator::check($data, [
            'name' => 'required|string|min:2|max:255',
            'description' => 'string|max:1000',
            'capabilities' => 'array',
            'configuration' => 'array',
        ]);

        if (empty($validatedData)) {
            $validator = SimpleValidator::make($data, [
                'name' => 'required|string|min:2|max:255',
                'description' => 'string|max:1000',
                'capabilities' => 'array',
                'configuration' => 'array',
            ]);
            throw new \InvalidArgumentException('Validation failed: ' . $validator->getFirstError());
        }

        // 生成唯一的Agent ID
        $agentId = $this->generateAgentId($validatedData['name']);

        // 创建Agent
        $agent = Agent::create([
            'user_id' => $user->id,
            'name' => $validatedData['name'],
            'description' => $validatedData['description'] ?? null,
            'agent_id' => $agentId,
            'capabilities' => $validatedData['capabilities'] ?? [],
            'configuration' => $validatedData['configuration'] ?? [],
            'status' => Agent::STATUS_PENDING,
            'metadata' => [],
        ]);

        // 记录日志
        $this->logger->audit('agent_created', $user->id, [
            'agent_id' => $agent->id,
            'agent_identifier' => $agentId,
            'name' => $agent->name,
        ]);

        // 分发事件
        $this->eventDispatcher->dispatch(new \App\Modules\MCP\Events\AgentCreated($agent));

        return $agent;
    }

    /**
     * 更新Agent
     */
    public function update(Agent $agent, array $data): Agent
    {
        // 验证数据
        $validatedData = SimpleValidator::check($data, [
            'name' => 'string|min:2|max:255',
            'description' => 'string|max:1000',
            'capabilities' => 'array',
            'configuration' => 'array',
            'status' => 'string|in:active,inactive,suspended,pending',
        ]);

        if (empty($validatedData)) {
            $validator = SimpleValidator::make($data, [
                'name' => 'string|min:2|max:255',
                'description' => 'string|max:1000',
                'capabilities' => 'array',
                'configuration' => 'array',
                'status' => 'string|in:active,inactive,suspended,pending',
            ]);
            throw new \InvalidArgumentException('Validation failed: ' . $validator->getFirstError());
        }

        // 记录原始状态
        $originalStatus = $agent->status;

        // 更新Agent
        $agent->update($validatedData);

        // 如果状态发生变化，记录日志和分发事件
        if (isset($validatedData['status']) && $originalStatus !== $validatedData['status']) {
            $this->logger->audit('agent_status_changed', $agent->user_id, [
                'agent_id' => $agent->id,
                'old_status' => $originalStatus,
                'new_status' => $validatedData['status'],
            ]);

            $this->eventDispatcher->dispatch(new \App\Modules\MCP\Events\AgentStatusChanged($agent, $originalStatus));
        }

        // 记录更新日志
        $this->logger->audit('agent_updated', $agent->user_id, [
            'agent_id' => $agent->id,
            'updated_fields' => array_keys($validatedData),
        ]);

        return $agent->fresh();
    }

    /**
     * 删除Agent
     */
    public function delete(Agent $agent): bool
    {
        // 检查是否有活跃的任务
        $activeTasks = $agent->tasks()->whereIn('status', ['pending', 'in_progress'])->count();
        if ($activeTasks > 0) {
            throw new \InvalidArgumentException('Cannot delete agent with active tasks');
        }

        // 记录日志
        $this->logger->audit('agent_deleted', $agent->user_id, [
            'agent_id' => $agent->id,
            'agent_identifier' => $agent->agent_id,
            'name' => $agent->name,
        ]);

        // 分发事件
        $this->eventDispatcher->dispatch(new \App\Modules\MCP\Events\AgentDeleted($agent));

        // 删除Agent
        return $agent->delete();
    }

    /**
     * 获取用户的Agent列表
     */
    public function getUserAgents(User $user, array $filters = []): Collection
    {
        $query = Agent::byUser($user->id)->with(['user']);

        // 应用过滤器
        if (isset($filters['status'])) {
            $query->byStatus($filters['status']);
        }

        if (isset($filters['capability'])) {
            $query->withCapability($filters['capability']);
        }

        if (isset($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhere('agent_id', 'like', "%{$search}%");
            });
        }

        return $query->orderBy('created_at', 'desc')->get();
    }

    /**
     * 根据Agent ID查找Agent
     */
    public function findByAgentId(string $agentId): ?Agent
    {
        return Agent::where('agent_id', $agentId)->first();
    }

    /**
     * 激活Agent
     */
    public function activate(Agent $agent): Agent
    {
        $originalStatus = $agent->status;
        $agent->activate();

        // 记录日志
        $this->logger->audit('agent_activated', $agent->user_id, [
            'agent_id' => $agent->id,
            'previous_status' => $originalStatus,
        ]);

        // 分发事件
        $this->eventDispatcher->dispatch(new \App\Modules\MCP\Events\AgentActivated($agent));

        return $agent->fresh();
    }

    /**
     * 停用Agent
     */
    public function deactivate(Agent $agent): Agent
    {
        $originalStatus = $agent->status;
        $agent->deactivate();

        // 记录日志
        $this->logger->audit('agent_deactivated', $agent->user_id, [
            'agent_id' => $agent->id,
            'previous_status' => $originalStatus,
        ]);

        // 分发事件
        $this->eventDispatcher->dispatch(new \App\Modules\MCP\Events\AgentDeactivated($agent));

        return $agent->fresh();
    }

    /**
     * 更新Agent最后活跃时间
     */
    public function updateLastActive(Agent $agent): void
    {
        $agent->updateLastActive();
    }

    /**
     * 获取Agent统计信息
     */
    public function getAgentStats(Agent $agent): array
    {
        return $agent->getStats();
    }

    /**
     * 获取系统Agent统计信息
     */
    public function getSystemStats(): array
    {
        try {
            return [
                'total_agents' => Agent::count(),
                'active_agents' => Agent::active()->count(),
                'inactive_agents' => Agent::byStatus(Agent::STATUS_INACTIVE)->count(),
                'suspended_agents' => Agent::byStatus(Agent::STATUS_SUSPENDED)->count(),
                'pending_agents' => Agent::byStatus(Agent::STATUS_PENDING)->count(),
                'table_exists' => true,
            ];
        } catch (\Exception $e) {
            // 如果表不存在，返回默认值
            return [
                'total_agents' => 0,
                'active_agents' => 0,
                'inactive_agents' => 0,
                'suspended_agents' => 0,
                'pending_agents' => 0,
                'table_exists' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * 生成唯一的Agent ID
     */
    protected function generateAgentId(string $name): string
    {
        // 基于名称生成基础ID
        $baseId = Str::slug($name, '_');
        $baseId = Str::limit($baseId, 20, '');

        // 添加随机后缀确保唯一性
        $suffix = Str::random(8);
        $agentId = $baseId . '_' . $suffix;

        // 确保唯一性
        while (Agent::where('agent_id', $agentId)->exists()) {
            $suffix = Str::random(8);
            $agentId = $baseId . '_' . $suffix;
        }

        return $agentId;
    }
}
