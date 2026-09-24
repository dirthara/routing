<?php

declare(strict_types=1);

namespace Dirthara\Routing;

use UnitEnum;
use Stringable;
use Dirthara\Routing\Exception\InvalidRouteException;
use Dirthara\Routing\Exception\RouteNotFoundException;
use Dirthara\Routing\Exception\InvalidUrlParameterException;

final readonly class UrlGenerator
{
    public function __construct(
        private RouteCollection $routes,
    ) {}

    /**
     * @param array<string, scalar|Stringable> $parameters
     *
     * @throws InvalidRouteException
     * @throws RouteNotFoundException
     * @throws InvalidUrlParameterException
     */
    public function name(string|UnitEnum $name, array $parameters = []): string
    {
        $route = $this->routes->named($name) ?? throw RouteNotFoundException::forName($name);

        return $route->generatePath($parameters);
    }

    /**
     * @param array<string, scalar|Stringable> $parameters
     *
     * @throws InvalidRouteException
     * @throws RouteNotFoundException
     * @throws InvalidUrlParameterException
     */
    public function action(mixed $action, array $parameters = [], ?HttpMethod $method = null): string
    {
        $route = $this->routes->handledBy($action, $method) ?? throw RouteNotFoundException::forAction(
            $action,
            $method,
        );

        return $route->generatePath($parameters);
    }
}
