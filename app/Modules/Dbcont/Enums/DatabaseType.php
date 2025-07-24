<?php

namespace App\Modules\Dbcont\Enums;

enum DatabaseType: string
{
    case SQLITE = 'SQLITE';
    case MYSQL = 'MYSQL';
    case MARIADB = 'MARIADB';
}