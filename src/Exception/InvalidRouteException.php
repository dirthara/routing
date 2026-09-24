<?php

declare(strict_types=1);

namespace Dirthara\Routing\Exception;

use UnitEnum;
use Throwable;
use InvalidArgumentException;
use Dirthara\Routing\HttpMethod;

use function implode;
use function sprintf;
use function array_map;

final class InvalidRouteException extends InvalidArgumentException implements RoutingException
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

    public static function pathWithoutLeadingSlash(string $path): self
    {
        return new self(
            message: sprintf('The route path "%s" must start with a "/".', self::printable($path)),
            context: ['path' => $path],
        );
    }

    public static function unbalancedBraces(string $path): self
    {
        return new self(
            message: sprintf(
                'The route path "%s" has a "{" or "}" outside a {parameter} placeholder.',
                self::printable($path),
            ),
            context: ['path' => $path],
        );
    }

    public static function invalidParameterName(string $path, string $parameter): self
    {
        return new self(
            message: sprintf(
                'The route path "%s" has an invalid parameter name "%s": it must start with a letter or underscore, '
                . 'contain only letters, digits, and underscores, and be at most 32 characters long.',
                self::printable($path),
                self::printable($parameter),
            ),
            context: ['path' => $path, 'parameter' => $parameter],
        );
    }

    public static function duplicateParameter(string $path, string $parameter): self
    {
        return new self(
            message: sprintf(
                'The route path "%s" uses the parameter "%s" more than once.',
                self::printable($path),
                $parameter,
            ),
            context: ['path' => $path, 'parameter' => $parameter],
        );
    }

    public static function misplacedOptionalParameter(string $path, string $parameter): self
    {
        return new self(
            message: sprintf(
                'The optional parameter "%2$s" of the route path "%1$s" must be the whole last segment of the path, as '
                . 'in "/posts/{page?}".',
                self::printable($path),
                $parameter,
            ),
            context: ['path' => $path, 'parameter' => $parameter],
        );
    }

    public static function unknownParameter(string $path, string $parameter): self
    {
        return new self(
            message: sprintf(
                'Cannot constrain "%s": the route path "%s" has no such parameter.',
                self::printable($parameter),
                self::printable($path),
            ),
            context: ['path' => $path, 'parameter' => $parameter],
        );
    }

    public static function invalidPattern(string $path, string $parameter, string $pattern): self
    {
        return new self(
            message: sprintf(
                'The pattern "%s" for the parameter "%s" of the route path "%s" is not a valid regular expression.',
                self::printable($pattern),
                $parameter,
                self::printable($path),
            ),
            context: ['path' => $path, 'parameter' => $parameter, 'pattern' => $pattern],
        );
    }

    public static function emptyName(string $path): self
    {
        return new self(
            message: sprintf('The route "%s" cannot be given an empty name.', self::printable($path)),
            context: ['path' => $path],
        );
    }

    public static function duplicateName(string|UnitEnum $name, string $firstPath, string $secondPath): self
    {
        return new self(
            message: sprintf(
                'The name "%s" is given to more than one route: "%s" and "%s".',
                self::printableName($name),
                self::printable($firstPath),
                self::printable($secondPath),
            ),
            context: ['name' => $name, 'paths' => [$firstPath, $secondPath]],
        );
    }

    /**
     * @param list<string> $routes
     */
    public static function ambiguousAction(mixed $action, ?HttpMethod $method, array $routes): self
    {
        return new self(
            message: sprintf(
                'The action "%s" is the handler of more than one %sroute: %s.%s',
                self::printableAction($action),
                $method === null ? '' : $method->value . ' ',
                implode(', ', array_map(self::printable(...), $routes)),
                $method === null ? ' Pass a method to choose one.' : '',
            ),
            context: ['action' => $action, 'method' => $method?->value, 'routes' => $routes],
        );
    }
}
