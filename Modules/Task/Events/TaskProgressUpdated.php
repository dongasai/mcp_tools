<?php

namespace Modules\Task\Events;

use Modules\Task\Models\Task;

class TaskProgressUpdated
{
    public function __construct(
        public readonly Task $task,
        public readonly int|null $previousProgress
    ) {}
}
