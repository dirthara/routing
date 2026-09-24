<?php

declare(strict_types=1);

namespace Dirthara\Routing;

use UnitEnum;

final class Route
{
    public HttpMethod $method;

    public string $path;

    public string|UnitEnum $name;

    public function handler(): mixed
    {
        // @todo

        return '';
    }

    public function name(string|UnitEnum $name): self
    {
        // @todo

        return $this;
    }

    public function where(string $parameter, string|RoutePattern $pattern): self
    {
        // @todo

        return $this;
    }

    /**
     * @param array<string, string|RoutePattern> $constraints
     */
    public function whereMany(array $constraints): self
    {
        // @todo

        return $this;
    }

    /**
     * @return array<string, string>
     */
    public function constraints(): array
    {
        // @todo

        return [];
    }
}
