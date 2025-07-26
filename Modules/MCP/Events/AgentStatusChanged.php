<?php

namespace Modules\MCP\Events;

use Modules\MCP\Models\Agent;

class AgentStatusChanged
{
    public function __construct(
        public readonly Agent $agent,
        public readonly string $previousStatus
    ) {}
}
