<?php

namespace App\Modules\Project\Events;

use App\Modules\Project\Models\Project;

class ProjectAgentChanged
{
    public function __construct(
        public readonly Project $project,
        public readonly ?int $previousAgentId
    ) {}
}
