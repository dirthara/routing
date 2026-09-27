<?php

declare(strict_types=1);

namespace Dirthara\Routing\Contract;

use UnitEnum;
use Stringable;
use Dirthara\Routing\HttpMethod;
use Dirthara\Routing\Exception\InvalidRouteException;
use Dirthara\Routing\Exception\RouteNotFoundException;
use Dirthara\Routing\Exception\InvalidUrlParameterException;

interface UrlGenerator
{
    /**
     * @param array<string, scalar|Stringable> $parameters
     *
     * @throws InvalidRouteException
     * @throws RouteNotFoundException
     * @throws InvalidUrlParameterException
     */
    public function name(string|UnitEnum $name, array $parameters = []): string;

    /**
     * @param array<string, scalar|Stringable> $parameters
     *
     * @throws InvalidRouteException
     * @throws RouteNotFoundException
     * @throws InvalidUrlParameterException
     */
    public function action(mixed $action, array $parameters = [], ?HttpMethod $method = null): string;
}
