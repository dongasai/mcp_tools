<?php

namespace Modules\MCP\Events;

use Modules\MCP\Models\Agent;

class AgentDeactivated
{
    public function __construct(
        public readonly Agent $agent
    ) {}
}
