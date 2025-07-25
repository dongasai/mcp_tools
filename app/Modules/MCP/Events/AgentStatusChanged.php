<?php

namespace App\Modules\MCP\Events;

use App\Modules\MCP\Models\Agent;

class AgentStatusChanged
{
    public function __construct(
        public readonly Agent $agent,
        public readonly string $previousStatus
    ) {}
}
