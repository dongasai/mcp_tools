<?php

namespace Modules\User\Events;

use Modules\User\Models\User;

class UserStatusChanged
{
    public function __construct(
        public readonly User $user,
        public readonly string $oldStatus,
        public readonly string $newStatus
    ) {}
}
