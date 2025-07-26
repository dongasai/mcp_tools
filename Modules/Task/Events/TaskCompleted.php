<?php

namespace Modules\Task\Events;

use Modules\Task\Models\Task;

class TaskCompleted
{
    public function __construct(
        public readonly Task $task
    ) {}
}
