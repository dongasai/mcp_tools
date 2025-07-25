<?php

namespace App\Modules\Mcp\Events;

use App\Modules\Mcp\Models\AgentQuestion;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class QuestionIgnored
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public AgentQuestion $question
    ) {}
}
