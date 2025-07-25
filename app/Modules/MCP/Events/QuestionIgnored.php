<?php

namespace App\Modules\MCP\Events;

use App\Modules\MCP\Models\AgentQuestion;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class QuestionIgnored
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public AgentQuestion $question
    ) {}
}
