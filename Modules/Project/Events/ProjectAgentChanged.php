<?php

namespace Modules\Project\Events;

use Modules\Project\Models\Project;

class ProjectAgentChanged
{
    public function __construct(
        public readonly Project $project,
        public readonly ?int $previousAgentId
    ) {}
}
