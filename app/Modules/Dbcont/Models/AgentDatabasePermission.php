<?php

namespace App\Modules\Dbcont\Models;

use App\Modules\Dbcont\Enums\PermissionLevel;
use App\Modules\MCP\Models\Agent;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AgentDatabasePermission extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'agent_id',
        'database_connection_id',
        'permission_level',
        'allowed_tables',
        'denied_operations',
        'max_query_time',
        'max_result_rows',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'permission_level' => PermissionLevel::class,
        'allowed_tables' => 'array',
        'denied_operations' => 'array',
    ];

    /**
     * Get the agent that owns the permission.
     */
    public function agent(): BelongsTo
    {
        return $this->belongsTo(Agent::class);
    }

    /**
     * Get the database connection that owns the permission.
     */
    public function databaseConnection(): BelongsTo
    {
        return $this->belongsTo(DatabaseConnection::class);
    }
}