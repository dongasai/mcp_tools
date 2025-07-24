<?php

namespace App\Modules\Dbcont\Events;

use App\Modules\Dbcont\Models\SqlExecutionLog;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SqlExecuted
{
    use Dispatchable, SerializesModels;

    /**
     * SQL执行日志实例
     *
     * @var SqlExecutionLog
     */
    public SqlExecutionLog $log;

    /**
     * 创建新的事件实例
     */
    public function __construct(SqlExecutionLog $log)
    {
        $this->log = $log;
    }
}