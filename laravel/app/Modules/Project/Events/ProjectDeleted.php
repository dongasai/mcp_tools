<?php

namespace App\Modules\Project\Events;

use App\Modules\Project\Models\Project;

class ProjectDeleted
{
    public function __construct(
        public readonly Project $project
    ) {}
}
