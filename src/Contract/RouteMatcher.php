<?php

declare(strict_types=1);

namespace Dirthara\Routing\Contract;

use Dirthara\Routing\HttpMethod;
use Dirthara\Routing\RouteMatch;
use Dirthara\Routing\Exception\RouteNotFoundException;
use Dirthara\Routing\Exception\MethodNotAllowedException;

interface RouteMatcher
{
    /**
     * @throws RouteNotFoundException
     * @throws MethodNotAllowedException
     */
    public function match(HttpMethod|string $method, string $path): RouteMatch;
}
