<?php

declare(strict_types=1);

namespace Dirthara\Routing;

use Stringable;

final readonly class Router
{
    public function __construct(
        public TrailingSlash $trailingSlash = TrailingSlash::Ignore,
    ) {}

    public function add(HttpMethod $method, string $path, mixed $handler): Route
    {
        // @todo
    }

    public function get(string $path, mixed $handler): Route
    {
        return $this->add(HttpMethod::Get, $path, $handler);
    }

    public function head(string $path, mixed $handler): Route
    {
        return $this->add(HttpMethod::Head, $path, $handler);
    }

    public function post(string $path, mixed $handler): Route
    {
        return $this->add(HttpMethod::Post, $path, $handler);
    }

    public function put(string $path, mixed $handler): Route
    {
        return $this->add(HttpMethod::Put, $path, $handler);
    }

    public function patch(string $path, mixed $handler): Route
    {
        return $this->add(HttpMethod::Patch, $path, $handler);
    }

    public function delete(string $path, mixed $handler): Route
    {
        return $this->add(HttpMethod::Delete, $path, $handler);
    }

    public function options(string $path, mixed $handler): Route
    {
        return $this->add(HttpMethod::Options, $path, $handler);
    }

    public function match(HttpMethod|string $method, string $path): RouteMatch
    {
        // @todo
    }

    /**
     * @param array<string, scalar|Stringable> $parameters
     */
    public function url(string $name, array $parameters = []): string
    {
        // @todo
    }
}
