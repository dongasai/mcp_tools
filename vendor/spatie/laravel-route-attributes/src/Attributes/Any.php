<?php

namespace Spatie\RouteAttributes\Attributes;

use Attribute;
use Illuminate\Routing\Router;

#[Attribute(Attribute::TARGET_METHOD | Attribute::IS_REPEATABLE)]
class Any extends Route
{
    public function __construct(
        string $uri,
        ?string $name = null,
        array | string $middleware = [],
        array | string $withoutMiddleware = [],
    ) {
        parent::__construct(
            methods: Router::$verbs,
            uri: $uri,
            name: $name,
            middleware: $middleware,
            withoutMiddleware: $withoutMiddleware
        );
    }
}
