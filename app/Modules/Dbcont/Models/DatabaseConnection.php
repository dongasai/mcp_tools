<?php

namespace App\Modules\Dbcont\Models;

use App\Modules\Dbcont\Enums\DatabaseType;
use App\Modules\Dbcont\Enums\ConnectionStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DatabaseConnection extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'project_id',
        'name',
        'type',
        'host',
        'port',
        'database',
        'username',
        'password',
        'options',
        'status',
        'last_tested_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'type' => DatabaseType::class,
        'status' => ConnectionStatus::class,
        'options' => 'array',
        'last_tested_at' => 'datetime',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
    ];

    /**
     * Get the agent permissions for the database connection.
     */
    public function agentPermissions(): HasMany
    {
        return $this->hasMany(AgentDatabasePermission::class);
    }

    /**
     * Get the SQL execution logs for the database connection.
     */
    public function sqlExecutionLogs(): HasMany
    {
        return $this->hasMany(SqlExecutionLog::class);
    }
}