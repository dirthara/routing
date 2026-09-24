<?php

declare(strict_types=1);

namespace Dirthara\Routing\Exception;

use UnitEnum;
use Throwable;
use RuntimeException;

use function sprintf;

final class RouteNotFoundException extends RuntimeException implements RoutingException
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

    public static function forPath(string $method, string $path): self
    {
        return new self(
            message: sprintf('No route matches %s "%s".', self::printable($method), self::printable($path)),
            context: ['method' => $method, 'path' => $path],
        );
    }

    public static function forName(string|UnitEnum $name): self
    {
        return new self(message: sprintf('No route is named "%s".', self::printableName($name)), context: [
            'name' => $name,
        ]);
    }
}
