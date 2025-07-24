<?php

namespace App\Modules\User\Models;

use Dcat\Admin\Models\Permission;

class UserAdminPermission extends Permission
{
    protected $table = 'user_admin_permissions';

    public function getTable()
    {
        return 'user_admin_permissions';
    }
}
