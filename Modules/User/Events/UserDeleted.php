<?php

namespace Modules\User\Events;

use Modules\User\Models\User;

class UserDeleted
{
    public function __construct(
        public readonly User $user
    ) {}
}
