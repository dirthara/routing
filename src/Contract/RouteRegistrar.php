<?php

declare(strict_types=1);

namespace Dirthara\Routing\Contract;

use Dirthara\Routing\HttpMethod;
use Dirthara\Routing\Exception\InvalidRouteException;

interface RouteRegistrar
{
    /**
     * @throws InvalidRouteException
     */
    public function add(HttpMethod $method, string $path, mixed $handler): Route;

    /**
     * @throws InvalidRouteException
     */
    public function get(string $path, mixed $handler): Route;

    /**
     * @throws InvalidRouteException
     */
    public function head(string $path, mixed $handler): Route;

    /**
     * @throws InvalidRouteException
     */
    public function post(string $path, mixed $handler): Route;

    /**
     * @throws InvalidRouteException
     */
    public function put(string $path, mixed $handler): Route;

    /**
     * @throws InvalidRouteException
     */
    public function patch(string $path, mixed $handler): Route;

    /**
     * @throws InvalidRouteException
     */
    public function delete(string $path, mixed $handler): Route;

    /**
     * @throws InvalidRouteException
     */
    public function options(string $path, mixed $handler): Route;
}
