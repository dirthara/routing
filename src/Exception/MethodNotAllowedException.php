<?php

namespace Dirthara\Routing\Exception;

use LogicException;
use Throwable;

final class MethodNotAllowedException extends LogicException implements RoutingException
{
    use HasExceptionContext;

    public function __construct(string $message = "", int $code = 0, ?Throwable $previous = null, array $context = [])
    {
        parent::__construct($message, $code, $previous);

        $this->context = $context;
    }
}