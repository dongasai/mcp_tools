<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Agent extends Model
{
    protected $fillable = [
        'agent_id',
        'name',
        'type',
        'access_token',
        'permissions',
        'allowed_projects',
        'allowed_actions',
        'status',
        'last_active_at',
        'token_expires_at',
        'user_id',
    ];

    protected $casts = [
        'permissions' => 'array',
        'allowed_projects' => 'array',
        'allowed_actions' => 'array',
        'last_active_at' => 'datetime',
        'token_expires_at' => 'datetime',
    ];

    protected $hidden = [
        'access_token',
    ];

    /**
     * Get the user that owns the agent.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the tasks assigned to this agent.
     */
    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class, 'agent_id', 'agent_id');
    }

    /**
     * Get active tasks for this agent.
     */
    public function activeTasks(): HasMany
    {
        return $this->hasMany(Task::class, 'agent_id', 'agent_id')
                    ->whereIn('status', ['claimed', 'in_progress']);
    }

    /**
     * Scope a query to only include active agents.
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope a query to only include online agents (active in last 5 minutes).
     */
    public function scopeOnline($query)
    {
        return $query->where('status', 'active')
                    ->where('last_active_at', '>=', now()->subMinutes(5));
    }

    /**
     * Check if agent has permission to access a project.
     */
    public function canAccessProject(int $projectId): bool
    {
        $allowedProjects = $this->allowed_projects ?? [];
        return in_array($projectId, $allowedProjects);
    }

    /**
     * Check if agent has permission to perform an action.
     */
    public function canPerformAction(string $action): bool
    {
        $allowedActions = $this->allowed_actions ?? [];
        return in_array($action, $allowedActions);
    }

    /**
     * Generate a new access token for the agent.
     */
    public function generateAccessToken(): string
    {
        $token = 'mcp_token_' . Str::random(40);
        $this->access_token = $token;
        $this->token_expires_at = now()->addSeconds(config('mcp.access_control.token_expiry', 86400));
        $this->save();

        return $token;
    }

    /**
     * Check if the agent's token is expired.
     */
    public function isTokenExpired(): bool
    {
        return $this->token_expires_at && $this->token_expires_at->isPast();
    }

    /**
     * Update the agent's last active timestamp.
     */
    public function updateLastActive(): void
    {
        $this->last_active_at = now();
        $this->save();
    }

    /**
     * Get projects this agent can access.
     */
    public function accessibleProjects()
    {
        if (empty($this->allowed_projects)) {
            return collect();
        }

        return Project::whereIn('id', $this->allowed_projects)->get();
    }
}
