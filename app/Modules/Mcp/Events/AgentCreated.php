<?php

namespace App\Modules\Mcp\Events;

use App\Modules\Mcp\Models\Agent;

class AgentCreated
{
    public function __construct(
        public readonly Agent $agent
    ) {}
}
