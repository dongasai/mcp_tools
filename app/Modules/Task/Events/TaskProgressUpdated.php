<?php

namespace App\Modules\Task\Events;

use App\Modules\Task\Models\Task;

class TaskProgressUpdated
{
    public function __construct(
        public readonly Task $task,
        public readonly int|null $previousProgress
    ) {}
}
