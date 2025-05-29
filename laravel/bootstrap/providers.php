<?php

return [
    App\Providers\AppServiceProvider::class,
    App\Modules\Core\Providers\CoreServiceProvider::class,
    App\Modules\User\Providers\UserServiceProvider::class,
    App\Modules\Agent\Providers\AgentServiceProvider::class,
    App\Modules\Project\Providers\ProjectServiceProvider::class,
    App\Modules\Task\Providers\TaskServiceProvider::class,
];
