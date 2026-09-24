<?php

declare(strict_types=1);

namespace Dirthara\Routing;

use UnitEnum;
use Stringable;
use Dirthara\Routing\Exception\InvalidRouteException;
use Dirthara\Routing\Exception\InvalidUrlParameterException;

use function is_bool;
use function array_diff;
use function array_keys;
use function preg_match;
use function array_values;
use function rawurlencode;
use function array_key_exists;
use function set_error_handler;
use function restore_error_handler;

final class Route
{
    private const string DEFAULT_PATTERN = '[^/]+';

    public private(set) string|UnitEnum|null $name = null;

    /**
     * @var array<string, string>
     */
    private array $constraints = [];

    private readonly RoutePath $routePath;

    /**
     * @throws InvalidRouteException
     */
    public function __construct(
        public readonly HttpMethod $method,
        public readonly string $path,
        private readonly mixed $handler,
    ) {
        $this->routePath = new RoutePath($path);
    }

    public function handler(): mixed
    {
        return $this->handler;
    }

    /**
     * @throws InvalidRouteException
     */
    public function name(string|UnitEnum $name): self
    {
        if ($name === '') {
            throw InvalidRouteException::emptyName($this->path);
        }

        $this->name = $name;

        return $this;
    }

    /**
     * @throws InvalidRouteException
     */
    public function where(string $parameter, string|RoutePattern $pattern): self
    {
        $pattern = $pattern instanceof RoutePattern ? $pattern->value : $pattern;

        if (!array_key_exists($parameter, $this->patterns())) {
            throw InvalidRouteException::unknownParameter($this->path, $parameter);
        }

        if (!self::isValidPattern($pattern)) {
            throw InvalidRouteException::invalidPattern($this->path, $parameter, $pattern);
        }

        $this->constraints[$parameter] = $pattern;

        return $this;
    }

    /**
     * @param array<string, string|RoutePattern> $constraints
     *
     * @throws InvalidRouteException
     */
    public function whereMany(array $constraints): self
    {
        foreach ($constraints as $parameter => $pattern) {
            $this->where($parameter, $pattern);
        }

        return $this;
    }

    /**
     * @return array<string, string>
     */
    public function constraints(): array
    {
        return $this->constraints;
    }

    /**
     * @return list<string>
     */
    public function parameters(): array
    {
        return $this->routePath->parameters();
    }

    /**
     * @return array<string, string>|null
     */
    public function matches(string $path, TrailingSlash $trailingSlash = TrailingSlash::Ignore): ?array
    {
        return $this->routePath->match($path === '' ? '/' : $path, $this->patterns(), $trailingSlash);
    }

    /**
     * @param array<string, scalar|Stringable> $parameters
     *
     * @throws InvalidUrlParameterException
     */
    public function generatePath(array $parameters = []): string
    {
        $patterns = $this->patterns();
        $unknown = array_diff(array_keys($parameters), array_keys($patterns));

        if ($unknown !== []) {
            throw InvalidUrlParameterException::unknownParameters($this->name, $this->path, array_values($unknown));
        }

        $encodedValues = [];

        foreach ($patterns as $parameter => $pattern) {
            if (!array_key_exists($parameter, $parameters)) {
                throw InvalidUrlParameterException::missingParameter($this->name, $this->path, $parameter);
            }

            $encodedValue = rawurlencode(self::stringValue($parameters[$parameter]));

            if (preg_match('~^(?:' . $pattern . ')$~D', $encodedValue) !== 1) {
                throw InvalidUrlParameterException::patternMismatch($this->name, $this->path, $parameter, $pattern);
            }

            $encodedValues[$parameter] = $encodedValue;
        }

        return $this->routePath->build($encodedValues);
    }

    private static function isValidPattern(string $pattern): bool
    {
        set_error_handler(static fn(): bool => true);

        try {
            return preg_match('~^(?:' . $pattern . ')$~D', subject: '') !== false;
        } finally {
            restore_error_handler();
        }
    }

    /**
     * @param scalar|Stringable $value
     */
    private static function stringValue(int|float|string|bool|Stringable $value): string
    {
        if (is_bool($value)) {
            return $value ? '1' : '0';
        }

        return (string) $value;
    }

    /**
     * @return array<string, string>
     */
    private function patterns(): array
    {
        $patterns = [];

        foreach ($this->routePath->parameters() as $parameter) {
            $patterns[$parameter] = $this->constraints[$parameter] ?? self::DEFAULT_PATTERN;
        }

        return $patterns;
    }
}
