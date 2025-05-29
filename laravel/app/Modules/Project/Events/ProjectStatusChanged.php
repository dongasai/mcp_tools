<?php

namespace App\Modules\Project\Events;

use App\Modules\Project\Models\Project;

class ProjectStatusChanged
{
    public function __construct(
        public readonly Project $project,
        public readonly string $previousStatus
    ) {}
}
