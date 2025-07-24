<?php

namespace App\Modules\Dbcont\Events;

use App\Modules\Dbcont\Models\DatabaseConnection;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class DatabaseConnected
{
    use Dispatchable, SerializesModels;

    /**
     * 数据库连接实例
     *
     * @var DatabaseConnection
     */
    public DatabaseConnection $connection;

    /**
     * 连接状态
     *
     * @var bool
     */
    public bool $success;

    /**
     * 错误消息
     *
     * @var string|null
     */
    public ?string $errorMessage;

    /**
     * 创建新的事件实例
     */
    public function __construct(DatabaseConnection $connection, bool $success, ?string $errorMessage = null)
    {
        $this->connection = $connection;
        $this->success = $success;
        $this->errorMessage = $errorMessage;
    }
}