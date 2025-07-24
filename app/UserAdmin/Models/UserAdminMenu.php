<?php

namespace App\UserAdmin\Models;

use Dcat\Admin\Models\Menu;

class UserAdminMenu extends Menu
{
    protected $table = 'user_admin_menu';

    public function getTable()
    {
        return 'user_admin_menu';
    }
}
