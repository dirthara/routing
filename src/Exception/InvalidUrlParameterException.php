<?php

declare(strict_types=1);

namespace Dirthara\Routing\Exception;

use UnitEnum;
use Throwable;
use InvalidArgumentException;

use function implode;
use function sprintf;
use function array_map;

final class InvalidUrlParameterException extends InvalidArgumentException implements RoutingException
{
    use HasExceptionContext;

    /**
     * @param array<string, mixed> $context
     */
    public function __construct(string $message = '', int $code = 0, ?Throwable $previous = null, array $context = [])
    {
        parent::__construct($message, $code, $previous);

        $this->context = $context;
    }

    public static function missingParameter(string|UnitEnum|null $name, string $path, string $parameter): self
    {
        return new self(
            message: sprintf(
                'Cannot generate a URL for %s: the parameter "%s" is missing.',
                self::describeRoute($name, $path),
                $parameter,
            ),
            context: ['name' => $name, 'path' => $path, 'parameter' => $parameter],
        );
    }

    /**
     * @param list<string> $parameters
     */
    public static function unknownParameters(string|UnitEnum|null $name, string $path, array $parameters): self
    {
        return new self(
            message: sprintf(
                'Cannot generate a URL for %s: it has no parameter named %s.',
                self::describeRoute($name, $path),
                implode(', ', array_map(static fn(string $parameter): string => sprintf(
                    '"%s"',
                    self::printable($parameter),
                ), $parameters)),
            ),
            context: ['name' => $name, 'path' => $path, 'parameters' => $parameters],
        );
    }

    /**
     * The value is left out of the message and the context, because a path parameter can hold a token.
     */
    public static function patternMismatch(
        string|UnitEnum|null $name,
        string $path,
        string $parameter,
        string $pattern,
    ): self {
        return new self(
            message: sprintf(
                'Cannot generate a URL for %s: the value of "%s" does not match its pattern "%s".',
                self::describeRoute($name, $path),
                $parameter,
                self::printable($pattern),
            ),
            context: ['name' => $name, 'path' => $path, 'parameter' => $parameter, 'pattern' => $pattern],
        );
    }

    private static function describeRoute(string|UnitEnum|null $name, string $path): string
    {
        $route = sprintf('the route "%s"', self::printable($path));

        return $name === null ? $route : sprintf('%s named "%s"', $route, self::printableName($name));
    }
}
