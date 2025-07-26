<?php

namespace Modules\Task\Events;

use Modules\Task\Models\Task;
use Modules\Task\Enums\TASKSTATUS;

class TaskStatusChanged
{
    public function __construct(
        public readonly Task $task,
        public readonly string|TASKSTATUS|null $previousStatus
    ) {}
}
