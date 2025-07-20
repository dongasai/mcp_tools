<?php

namespace App\Modules\User\Events;

use App\Modules\User\Models\User;

class UserRegistered
{
    public function __construct(
        public readonly User $user,
        public readonly ?string $verificationToken = null
    ) {}
}
