<?php

namespace App\Modules\Task\Events;

use App\Modules\Task\Models\Task;

class TaskCompleted
{
    public function __construct(
        public readonly Task $task
    ) {}
}
