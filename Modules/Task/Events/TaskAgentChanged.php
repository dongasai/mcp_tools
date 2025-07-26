<?php

namespace Modules\Task\Events;

use Modules\Task\Models\Task;

class TaskAgentChanged
{
    public function __construct(
        public readonly Task $task,
        public readonly ?int $previousAgentId
    ) {}
}
