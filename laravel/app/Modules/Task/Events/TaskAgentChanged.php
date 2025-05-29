<?php

namespace App\Modules\Task\Events;

use App\Modules\Task\Models\Task;

class TaskAgentChanged
{
    public function __construct(
        public readonly Task $task,
        public readonly ?int $previousAgentId
    ) {}
}
