<?php

declare(strict_types=1);

namespace Dirthara\Routing;

use UnitEnum;
use Stringable;
use Dirthara\Routing\Exception\InvalidRouteException;
use Dirthara\Routing\Exception\RouteNotFoundException;
use Dirthara\Routing\Exception\MethodNotAllowedException;
use Dirthara\Routing\Exception\InvalidUrlParameterException;

use function array_values;

final readonly class Router
{
    private RouteCollection $routes;

    private UrlGenerator $urls;

    public function __construct(
        public TrailingSlash $trailingSlash = TrailingSlash::Ignore,
    ) {
        $this->routes = new RouteCollection();
        $this->urls = new UrlGenerator($this->routes);
    }

    /**
     * @throws InvalidRouteException
     */
    public function add(HttpMethod $method, string $path, mixed $handler): Route
    {
        $route = new Route($method, $path, $handler);

        $this->routes->add($route);

        return $route;
    }

    /**
     * @throws InvalidRouteException
     */
    public function get(string $path, mixed $handler): Route
    {
        return $this->add(HttpMethod::Get, $path, $handler);
    }

    /**
     * @throws InvalidRouteException
     */
    public function head(string $path, mixed $handler): Route
    {
        return $this->add(HttpMethod::Head, $path, $handler);
    }

    /**
     * @throws InvalidRouteException
     */
    public function post(string $path, mixed $handler): Route
    {
        return $this->add(HttpMethod::Post, $path, $handler);
    }

    /**
     * @throws InvalidRouteException
     */
    public function put(string $path, mixed $handler): Route
    {
        return $this->add(HttpMethod::Put, $path, $handler);
    }

    /**
     * @throws InvalidRouteException
     */
    public function patch(string $path, mixed $handler): Route
    {
        return $this->add(HttpMethod::Patch, $path, $handler);
    }

    /**
     * @throws InvalidRouteException
     */
    public function delete(string $path, mixed $handler): Route
    {
        return $this->add(HttpMethod::Delete, $path, $handler);
    }

    /**
     * @throws InvalidRouteException
     */
    public function options(string $path, mixed $handler): Route
    {
        return $this->add(HttpMethod::Options, $path, $handler);
    }

    /**
     * @throws RouteNotFoundException
     * @throws MethodNotAllowedException
     */
    public function match(HttpMethod|string $method, string $path): RouteMatch
    {
        $methodName = $method instanceof HttpMethod ? $method->value : $method;
        $method = $method instanceof HttpMethod ? $method : HttpMethod::tryFrom($method);
        $getMatch = null;
        $allowedMethods = [];

        foreach ($this->routes as $route) {
            $parameters = $route->matches($path, $this->trailingSlash);

            if ($parameters === null) {
                continue;
            }

            if ($route->method === $method) {
                return new RouteMatch($route, $parameters);
            }

            $allowedMethods[$route->method->value] = $route->method->value;

            if ($route->method === HttpMethod::Get) {
                $getMatch ??= new RouteMatch($route, $parameters);
                $allowedMethods[HttpMethod::Head->value] = HttpMethod::Head->value;
            }
        }

        if ($method === HttpMethod::Head && $getMatch !== null) {
            return $getMatch;
        }

        if ($allowedMethods !== []) {
            throw MethodNotAllowedException::forMethod($methodName, $path, array_values($allowedMethods));
        }

        throw RouteNotFoundException::forPath($methodName, $path);
    }

    /**
     * @param array<string, scalar|Stringable> $parameters
     *
     * @throws InvalidRouteException
     * @throws RouteNotFoundException
     * @throws InvalidUrlParameterException
     */
    public function url(string|UnitEnum $name, array $parameters = []): string
    {
        return $this->urls->name($name, $parameters);
    }

    public function urls(): UrlGenerator
    {
        return $this->urls;
    }
}
