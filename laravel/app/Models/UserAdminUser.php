<?php

namespace App\Models;

use Dcat\Admin\Models\Administrator;

class UserAdminUser extends Administrator
{
    protected $table = 'user_admin_users';

    protected $fillable = [
        'username',
        'password',
        'name',
        'avatar',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
        ];
    }

    /**
     * 确保使用正确的表名
     */
    public function getTable()
    {
        return 'user_admin_users';
    }
}
