<?php

declare(strict_types=1);

namespace Dirthara\Routing\Exception;

use Throwable;
use RuntimeException;

use function implode;
use function sprintf;

final class MethodNotAllowedException extends RuntimeException implements RoutingException
{
    use HasExceptionContext;

    /**
     * The methods the path does have routes for, suitable for an Allow header.
     *
     * @var list<string>
     */
    public private(set) array $allowedMethods = [];

    /**
     * @param array<string, mixed> $context
     */
    public function __construct(string $message = '', int $code = 0, ?Throwable $previous = null, array $context = [])
    {
        parent::__construct($message, $code, $previous);

        $this->context = $context;
    }

    /**
     * @param list<string> $allowedMethods
     */
    public static function forMethod(string $method, string $path, array $allowedMethods): self
    {
        $exception = new self(
            message: sprintf(
                'The path "%s" has no route for %s; it allows %s.',
                self::printable($path),
                self::printable($method),
                implode(', ', $allowedMethods),
            ),
            context: ['method' => $method, 'path' => $path, 'allowedMethods' => $allowedMethods],
        );

        $exception->allowedMethods = $allowedMethods;

        return $exception;
    }
}
