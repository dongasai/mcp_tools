<?php

namespace Modules\Task\Events;

use Modules\Task\Models\Task;

class TaskCreated
{
    public function __construct(
        public readonly Task $task
    ) {}
}
