<?php

namespace App\Modules\Task\Events;

use App\Modules\Task\Models\Task;

class TaskStatusChanged
{
    public function __construct(
        public readonly Task $task,
        public readonly string $previousStatus
    ) {}
}
