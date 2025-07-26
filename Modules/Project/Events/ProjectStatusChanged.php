<?php

namespace Modules\Project\Events;

use Modules\Project\Models\Project;

class ProjectStatusChanged
{
    public function __construct(
        public readonly Project $project,
        public readonly string $previousStatus
    ) {}
}
