<?php

return [
    App\Providers\AppServiceProvider::class,
    App\Modules\Core\Providers\CoreServiceProvider::class,
    App\Modules\User\Providers\UserServiceProvider::class,
    App\Modules\Project\Providers\ProjectServiceProvider::class,
    App\Modules\Task\Providers\TaskServiceProvider::class,
    App\Modules\MCP\Providers\MCPServiceProvider::class,
    App\Modules\Dbcont\Providers\DbcontServiceProvider::class,
];
