<?php

declare(strict_types=1);

namespace Dirthara\Routing;

use Dirthara\Routing\Contract\Route as RouteContract;

final readonly class RouteMatch
{
    /**
     * @param array<string, string> $parameters
     */
    public function __construct(
        public RouteContract $route,
        public array $parameters,
    ) {}

    public function handler(): mixed
    {
        return $this->route->handler();
    }
}
