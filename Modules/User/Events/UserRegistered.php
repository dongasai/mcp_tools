<?php

namespace Modules\User\Events;

use Modules\User\Models\User;

class UserRegistered
{
    public function __construct(
        public readonly User $user,
        public readonly ?string $verificationToken = null
    ) {}
}
