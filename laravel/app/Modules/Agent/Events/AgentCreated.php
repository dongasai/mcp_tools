<?php

namespace App\Modules\Agent\Events;

use App\Modules\Agent\Models\Agent;

class AgentCreated
{
    public function __construct(
        public readonly Agent $agent
    ) {}
}
