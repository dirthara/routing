<?php

declare(strict_types=1);

namespace Dirthara\Routing\Contract;

use UnitEnum;
use Stringable;
use Dirthara\Routing\HttpMethod;
use Dirthara\Routing\RoutePattern;
use Dirthara\Routing\TrailingSlash;
use Dirthara\Routing\Exception\InvalidRouteException;
use Dirthara\Routing\Exception\InvalidUrlParameterException;

interface Route
{
    public HttpMethod $method { get; }

    public string $path { get; }

    public string|UnitEnum|null $name { get; }

    public function handler(): mixed;

    /**
     * @throws InvalidRouteException
     */
    public function name(string|UnitEnum $name): self;

    /**
     * @throws InvalidRouteException
     */
    public function where(string $parameter, string|RoutePattern $pattern): self;

    /**
     * @param array<string, string|RoutePattern> $constraints
     *
     * @throws InvalidRouteException
     */
    public function whereMany(array $constraints): self;

    /**
     * @return array<string, string>
     */
    public function constraints(): array;

    /**
     * @return list<string>
     */
    public function parameters(): array;

    /**
     * @return array<string, string>|null
     */
    public function matches(string $path, TrailingSlash $trailingSlash = TrailingSlash::Ignore): ?array;

    /**
     * @param array<string, scalar|Stringable> $parameters
     *
     * @throws InvalidUrlParameterException
     */
    public function generatePath(array $parameters = []): string;
}
