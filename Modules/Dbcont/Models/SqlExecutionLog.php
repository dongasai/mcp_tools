<?php

namespace Modules\Dbcont\Models;

use App\Modules\Agent\Models\Agent;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SqlExecutionLog extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'agent_id',
        'database_connection_id',
        'sql_statement',
        'execution_time',
        'rows_affected',
        'result_size',
        'status',
        'error_message',
        'ip_address',
        'executed_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'execution_time' => 'integer',
        'rows_affected' => 'integer',
        'result_size' => 'integer',
        'executed_at' => 'datetime',
    ];

    /**
     * Get the agent that owns the log.
     */
    public function agent(): BelongsTo
    {
        return $this->belongsTo(Agent::class);
    }

    /**
     * Get the database connection that owns the log.
     */
    public function databaseConnection(): BelongsTo
    {
        return $this->belongsTo(DatabaseConnection::class);
    }
}