<?php

namespace Modules\User\Events;

use Modules\User\Models\User;

class UserEmailVerified
{
    public function __construct(
        public readonly User $user
    ) {}
}
