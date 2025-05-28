<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Project extends Model
{
    protected $fillable = [
        'name',
        'description',
        'timezone',
        'status',
        'repositories',
        'settings',
        'user_id',
    ];

    protected $casts = [
        'repositories' => 'array',
        'settings' => 'array',
    ];

    /**
     * Get the user that owns the project.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the tasks for the project.
     */
    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }

    /**
     * Get active tasks for the project.
     */
    public function activeTasks(): HasMany
    {
        return $this->hasMany(Task::class)->whereIn('status', ['pending', 'claimed', 'in_progress']);
    }

    /**
     * Get completed tasks for the project.
     */
    public function completedTasks(): HasMany
    {
        return $this->hasMany(Task::class)->where('status', 'completed');
    }

    /**
     * Scope a query to only include active projects.
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Get the project's GitHub repositories.
     */
    public function getRepositoriesAttribute($value)
    {
        return $value ? json_decode($value, true) : [];
    }

    /**
     * Set the project's GitHub repositories.
     */
    public function setRepositoriesAttribute($value)
    {
        $this->attributes['repositories'] = json_encode($value);
    }
}
