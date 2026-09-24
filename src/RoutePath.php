<?php

declare(strict_types=1);

namespace Dirthara\Routing;

use Dirthara\Routing\Exception\InvalidRouteException;

use function count;
use function substr;
use function sprintf;
use function in_array;
use function array_pop;
use function preg_match;
use function preg_quote;
use function preg_split;
use function array_slice;
use function rawurldecode;
use function str_contains;
use function str_ends_with;
use function str_starts_with;
use function array_key_exists;

use const PREG_SPLIT_DELIM_CAPTURE;

/**
 * @internal
 */
final readonly class RoutePath
{
    private const string PLACEHOLDER = '~\{([^{}]*)\}~';

    private const string PARAMETER_NAME = '~^[A-Za-z_][A-Za-z0-9_]{0,31}$~D';

    public ?string $optionalParameter;

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
        $parts = $parts === false ? [$path] : $parts;
        $parameters = [];
        $optionalParameter = null;

        foreach ($parts as $index => $part) {
            if (($index % 2) === 0) {
                if (str_contains($part, '{') || str_contains($part, '}')) {
                    throw InvalidRouteException::unbalancedBraces($path);
                }

                continue;
            }

            $optional = str_ends_with($part, '?');
            $part = $optional ? substr($part, offset: 0, length: -1) : $part;

            if (preg_match(self::PARAMETER_NAME, $part) !== 1) {
                throw InvalidRouteException::invalidParameterName($path, $part);
            }

            if (in_array($part, $parameters, strict: true)) {
                throw InvalidRouteException::duplicateParameter($path, $part);
            }

            if ($optional) {
                if (!self::endsInOwnSegment($parts, $index)) {
                    throw InvalidRouteException::misplacedOptionalParameter($path, $part);
                }

                $optionalParameter = $part;
            }

            $parts[$index] = $part;
            $parameters[] = $part;
        }

        $this->parts = $parts;
        $this->optionalParameter = $optionalParameter;
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
        if ($this->optionalParameter === null) {
            return $this->requiredRegex($this->parts, $patterns, $trailingSlash);
        }

        $base = $this->quote($this->baseParts(), $patterns);
        $parameter = sprintf('(?P<%s>%s)', $this->optionalParameter, $patterns[$this->optionalParameter]);

        if ($trailingSlash === TrailingSlash::Strict && $base === '') {
            return sprintf('~^/%s?$~D', $parameter);
        }

        $suffix = $trailingSlash === TrailingSlash::Ignore ? '/?' : '';

        return sprintf('~^%s(?:/%s)?%s$~D', $base, $parameter, $suffix);
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
            // PCRE leaves trailing groups that did not take part out entirely, and the optional parameter is last.
            if (!array_key_exists($parameter, $matches)) {
                continue;
            }

            $parameters[$parameter] = rawurldecode($matches[$parameter]);
        }

        return $parameters;
    }

    /**
     * @param array<string, string> $encodedValues
     */
    public function build(array $encodedValues): string
    {
        if ($this->optionalParameter !== null && !array_key_exists($this->optionalParameter, $encodedValues)) {
            $path = $this->join($this->baseParts(), $encodedValues);

            return $path === '' ? '/' : $path;
        }

        return $this->join($this->parts, $encodedValues);
    }

    /**
     * @param list<string> $parts
     */
    private static function endsInOwnSegment(array $parts, int $index): bool
    {
        return $index === (count($parts) - 2) && $parts[$index + 1] === '' && str_ends_with($parts[$index - 1], '/');
    }

    /**
     * @return list<string>
     */
    private function baseParts(): array
    {
        $parts = array_slice($this->parts, offset: 0, length: -2);
        $last = array_pop($parts) ?? '';
        $parts[] = substr($last, offset: 0, length: -1);

        return $parts;
    }

    /**
     * @param list<string>          $parts
     * @param array<string, string> $patterns
     */
    private function requiredRegex(array $parts, array $patterns, TrailingSlash $trailingSlash): string
    {
        $suffix = '';

        if ($trailingSlash === TrailingSlash::Ignore && $this->path !== '/') {
            $last = count($parts) - 1;
            $parts[$last] = str_ends_with($parts[$last], '/')
                ? substr($parts[$last], offset: 0, length: -1)
                : $parts[$last];
            $suffix = '/?';
        }

        return sprintf('~^%s%s$~D', $this->quote($parts, $patterns), $suffix);
    }

    /**
     * @param list<string>          $parts
     * @param array<string, string> $patterns
     */
    private function quote(array $parts, array $patterns): string
    {
        $regex = '';

        foreach ($parts as $index => $part) {
            $regex .= ($index % 2) === 0
                ? preg_quote($part, delimiter: '~')
                : sprintf('(?P<%s>%s)', $part, $patterns[$part]);
        }

        return $regex;
    }

    /**
     * @param list<string>          $parts
     * @param array<string, string> $encodedValues
     */
    private function join(array $parts, array $encodedValues): string
    {
        $path = '';

        foreach ($parts as $index => $part) {
            $path .= ($index % 2) === 0 ? $part : $encodedValues[$part];
        }

        return $path;
    }
}
