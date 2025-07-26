<?php

namespace Modules\Dbcont\Enums;

enum PermissionLevel: string
{
    case READ_ONLY = 'READ_ONLY';
    case READ_WRITE = 'READ_WRITE';
    case ADMIN = 'ADMIN';
}