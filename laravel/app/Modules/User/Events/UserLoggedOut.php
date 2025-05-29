<?php

namespace App\Modules\User\Events;

use App\Modules\User\Models\User;

class UserLoggedOut
{
    public function __construct(
        public readonly User $user
    ) {}
}
