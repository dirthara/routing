<?php

declare(strict_types=1);

namespace Dirthara\Routing;

use UnitEnum;
use Countable;
use Traversable;
use ArrayIterator;
use IteratorAggregate;
use Dirthara\Routing\Exception\InvalidRouteException;

use function count;
use function array_map;

/**
 * @implements IteratorAggregate<int, Route>
 */
final class RouteCollection implements IteratorAggregate, Countable
{
    /**
     * @var list<Route>
     */
    private array $routes = [];

    public function add(Route $route): void
    {
        $this->routes[] = $route;
    }

    /**
     * @throws InvalidRouteException
     */
    public function named(string|UnitEnum $name): ?Route
    {
        $found = null;

        foreach ($this->routes as $route) {
            if ($route->name !== $name) {
                continue;
            }

            if ($found !== null) {
                throw InvalidRouteException::duplicateName($name, $found->path, $route->path);
            }

            $found = $route;
        }

        return $found;
    }

    /**
     * @throws InvalidRouteException
     */
    public function handledBy(mixed $action, ?HttpMethod $method = null): ?Route
    {
        $found = [];

        foreach ($this->routes as $route) {
            if ($route->handler() !== $action || $method !== null && $route->method !== $method) {
                continue;
            }

            $found[] = $route;
        }

        if (count($found) > 1) {
            throw InvalidRouteException::ambiguousAction(
                $action,
                $method,
                array_map(static fn(Route $route): string => $route->method->value . ' ' . $route->path, $found),
            );
        }

        return $found[0] ?? null;
    }

    /**
     * @return Traversable<int, Route>
     */
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->routes);
    }

    public function count(): int
    {
        return count($this->routes);
    }
}
