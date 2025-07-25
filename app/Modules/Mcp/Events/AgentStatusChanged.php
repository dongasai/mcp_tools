<?php

namespace App\Modules\Mcp\Events;

use App\Modules\Mcp\Models\Agent;

class AgentStatusChanged
{
    public function __construct(
        public readonly Agent $agent,
        public readonly string $previousStatus
    ) {}
}
