<?php

namespace App\Modules\MCP\Events;

use App\Modules\MCP\Models\Agent;

class AgentActivated
{
    public function __construct(
        public readonly Agent $agent
    ) {}
}
