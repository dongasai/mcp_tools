<?php

namespace Modules\Task\Events;

use Modules\Task\Models\Task;

class TaskDeleted
{
    public function __construct(
        public readonly Task $task
    ) {}
}
