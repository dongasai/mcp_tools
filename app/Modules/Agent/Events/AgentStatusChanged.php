<?php

namespace App\Modules\Agent\Events;

use App\Modules\Agent\Models\Agent;

class AgentStatusChanged
{
    public function __construct(
        public readonly Agent $agent,
        public readonly string $previousStatus
    ) {}
}
