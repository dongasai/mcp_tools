<?php

namespace App\Modules\Agent\Events;

use App\Modules\Agent\Models\AgentQuestion;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class QuestionCreated
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public AgentQuestion $question
    ) {}
}
