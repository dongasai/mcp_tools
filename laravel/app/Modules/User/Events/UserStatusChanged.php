<?php

namespace App\Modules\User\Events;

use App\Modules\User\Models\User;

class UserStatusChanged
{
    public function __construct(
        public readonly User $user,
        public readonly string $oldStatus,
        public readonly string $newStatus
    ) {}
}
