<?php

declare(strict_types=1);

namespace Dirthara\Routing;

use Dirthara\Routing\Exception\InvalidRouteException;

use function count;
use function substr;
use function sprintf;
use function in_array;
use function preg_match;
use function preg_quote;
use function preg_split;
use function rawurldecode;
use function str_contains;
use function str_ends_with;
use function str_starts_with;

use const PREG_SPLIT_DELIM_CAPTURE;

/**
 * @internal
 */
final readonly class RoutePath
{
    private const string PLACEHOLDER = '~\{([^{}]*)\}~';

    private const string PARAMETER_NAME = '~^[A-Za-z_][A-Za-z0-9_]{0,31}$~D';

    /**
     * @var list<string>
     */
    private array $parts;

    /**
     * @throws InvalidRouteException
     */
    public function __construct(
        public string $path,
    ) {
        if (!str_starts_with($path, '/')) {
            throw InvalidRouteException::pathWithoutLeadingSlash($path);
        }

        $parts = preg_split(self::PLACEHOLDER, $path, flags: PREG_SPLIT_DELIM_CAPTURE);
        $this->parts = $parts === false ? [$path] : $parts;
        $parameters = [];

        foreach ($this->parts as $index => $part) {
            if (($index % 2) === 0) {
                if (str_contains($part, '{') || str_contains($part, '}')) {
                    throw InvalidRouteException::unbalancedBraces($path);
                }

                continue;
            }

            if (preg_match(self::PARAMETER_NAME, $part) !== 1) {
                throw InvalidRouteException::invalidParameterName($path, $part);
            }

            if (in_array($part, $parameters, strict: true)) {
                throw InvalidRouteException::duplicateParameter($path, $part);
            }

            $parameters[] = $part;
        }
    }

    /**
     * @return list<string>
     */
    public function parameters(): array
    {
        $parameters = [];

        foreach ($this->parts as $index => $part) {
            if (($index % 2) === 0) {
                continue;
            }

            $parameters[] = $part;
        }

        return $parameters;
    }

    /**
     * @param array<string, string> $patterns
     */
    public function regex(array $patterns, TrailingSlash $trailingSlash): string
    {
        $parts = $this->parts;
        $suffix = '';

        if ($trailingSlash === TrailingSlash::Ignore && $this->path !== '/') {
            $last = count($parts) - 1;
            $parts[$last] = str_ends_with($parts[$last], '/')
                ? substr($parts[$last], offset: 0, length: -1)
                : $parts[$last];
            $suffix = '/?';
        }

        $regex = '';

        foreach ($parts as $index => $part) {
            $regex .= ($index % 2) === 0
                ? preg_quote($part, delimiter: '~')
                : sprintf('(?P<%s>%s)', $part, $patterns[$part]);
        }

        return sprintf('~^%s%s$~D', $regex, $suffix);
    }

    /**
     * @param array<string, string> $patterns
     *
     * @return array<string, string>|null
     */
    public function match(string $path, array $patterns, TrailingSlash $trailingSlash): ?array
    {
        $matches = [];

        if (preg_match($this->regex($patterns, $trailingSlash), $path, $matches) !== 1) {
            return null;
        }

        $parameters = [];

        foreach ($this->parameters() as $parameter) {
            $parameters[$parameter] = rawurldecode($matches[$parameter]);
        }

        return $parameters;
    }

    /**
     * @param array<string, string> $encodedValues
     */
    public function build(array $encodedValues): string
    {
        $path = '';

        foreach ($this->parts as $index => $part) {
            $path .= ($index % 2) === 0 ? $part : $encodedValues[$part];
        }

        return $path;
    }
}
