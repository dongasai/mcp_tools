<?php

namespace Modules\User\Events;

use Modules\User\Models\User;

class UserLoggedIn
{
    public function __construct(
        public readonly User $user
    ) {}
}
