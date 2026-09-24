<?php

declare(strict_types=1);

namespace Dirthara\Routing\Tests\Exception;

use RuntimeException;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use Dirthara\Routing\Exception\RoutingException;
use Dirthara\Routing\Exception\MethodNotAllowedException;

final class MethodNotAllowedExceptionTest extends TestCase
{
    #[Test]
    public function it_carries_nothing_by_default(): void
    {
        $exception = new MethodNotAllowedException();

        self::assertInstanceOf(RoutingException::class, $exception);
        self::assertInstanceOf(RuntimeException::class, $exception);
        self::assertSame('', $exception->getMessage());
        self::assertSame(0, $exception->getCode());
        self::assertNull($exception->getPrevious());
        self::assertSame([], $exception->context);
        self::assertSame([], $exception->allowedMethods);
    }

    #[Test]
    public function it_keeps_a_previous_exception_and_its_context(): void
    {
        $previous = new RuntimeException('cause');
        $exception = new MethodNotAllowedException('message', 3, $previous, ['path' => '/users']);

        self::assertSame('message', $exception->getMessage());
        self::assertSame(3, $exception->getCode());
        self::assertSame($previous, $exception->getPrevious());
        self::assertSame(['path' => '/users'], $exception->context);
    }

    #[Test]
    public function it_merges_what_is_added_to_its_context(): void
    {
        $exception = new MethodNotAllowedException(context: ['path' => '/users', 'kept' => true]);

        self::assertSame($exception, $exception->addContext(['path' => '/posts', 'host' => 'example.com']));
        self::assertSame(['path' => '/posts', 'kept' => true, 'host' => 'example.com'], $exception->context);
    }

    #[Test]
    public function it_describes_the_methods_the_path_allows(): void
    {
        $exception = MethodNotAllowedException::forMethod('PUT', '/users', ['GET', 'HEAD', 'POST']);

        self::assertSame(
            'The path "/users" has no route for PUT; it allows GET, HEAD, POST.',
            $exception->getMessage(),
        );
        self::assertSame(['GET', 'HEAD', 'POST'], $exception->allowedMethods);
        self::assertSame(
            ['method' => 'PUT', 'path' => '/users', 'allowedMethods' => ['GET', 'HEAD', 'POST']],
            $exception->context,
        );
    }
}
