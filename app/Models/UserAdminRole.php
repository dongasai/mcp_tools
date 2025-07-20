<?php

namespace App\Models;

use Dcat\Admin\Models\Role;

class UserAdminRole extends Role
{
    protected $table = 'user_admin_roles';

    public function getTable()
    {
        return 'user_admin_roles';
    }
}
