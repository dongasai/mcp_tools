<?php

namespace Modules\MCP\Events;

use Modules\MCP\Models\Agent;

class AgentCreated
{
    public function __construct(
        public readonly Agent $agent
    ) {}
}
