<?php

namespace App\Models;

use Dcat\Admin\Models\Permission;

class UserAdminPermission extends Permission
{
    protected $table = 'user_admin_permissions';

    public function getTable()
    {
        return 'user_admin_permissions';
    }
}
