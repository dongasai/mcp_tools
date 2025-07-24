<?php

namespace App\Modules\Task\Events;

use App\Modules\Task\Models\Task;
use App\Modules\Task\Enums\TASKSTATUS;

class TaskStatusChanged
{
    public function __construct(
        public readonly Task $task,
        public readonly string|TASKSTATUS|null $previousStatus
    ) {}
}
