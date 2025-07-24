<?php

namespace App\Modules\Dbcont\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PermissionViolation
{
    use Dispatchable, SerializesModels;

    /**
     * Agent ID
     *
     * @var int
     */
    public int $agentId;

    /**
     * 数据库连接ID
     *
     * @var int
     */
    public int $connectionId;

    /**
     * 违规类型
     *
     * @var string
     */
    public string $violationType;

    /**
     * 违规详情
     *
     * @var array
     */
    public array $details;

    /**
     * 创建新的事件实例
     */
    public function __construct(int $agentId, int $connectionId, string $violationType, array $details = [])
    {
        $this->agentId = $agentId;
        $this->connectionId = $connectionId;
        $this->violationType = $violationType;
        $this->details = $details;
    }
}