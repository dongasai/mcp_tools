<?php

namespace Modules\MCP\Events;

use Modules\MCP\Models\AgentQuestion;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class QuestionCreated
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public AgentQuestion $question
    ) {}
}
