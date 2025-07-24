<?php

namespace App\Modules\Dbcont\Enums;

enum ConnectionStatus: string
{
    case ACTIVE = 'ACTIVE';
    case INACTIVE = 'INACTIVE';
    case ERROR = 'ERROR';
}