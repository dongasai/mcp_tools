<?php

namespace Modules\User\Events;

use Modules\User\Models\User;

class PasswordResetRequested
{
    public function __construct(
        public readonly User $user,
        public readonly string $token
    ) {}
}
