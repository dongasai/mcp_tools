<?php

namespace Modules\Project\Events;

use Modules\Project\Models\Project;

class ProjectDeleted
{
    public function __construct(
        public readonly Project $project
    ) {}
}
