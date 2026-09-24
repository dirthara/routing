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
