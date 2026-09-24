<?php

declare(strict_types=1);

namespace Dirthara\Routing\Tests\Exception;

use RuntimeException;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use Dirthara\Routing\Tests\Fixtures\RouteName;
use Dirthara\Routing\Exception\RoutingException;
use Dirthara\Routing\Exception\RouteNotFoundException;

final class RouteNotFoundExceptionTest extends TestCase
{
    #[Test]
    public function it_carries_nothing_by_default(): void
    {
        $exception = new RouteNotFoundException();

        self::assertInstanceOf(RoutingException::class, $exception);
        self::assertInstanceOf(RuntimeException::class, $exception);
        self::assertSame('', $exception->getMessage());
        self::assertSame(0, $exception->getCode());
        self::assertNull($exception->getPrevious());
        self::assertSame([], $exception->context);
    }

    #[Test]
    public function it_keeps_a_previous_exception_and_its_context(): void
    {
        $previous = new RuntimeException('cause');
        $exception = new RouteNotFoundException('message', 3, $previous, ['path' => '/users']);

        self::assertSame('message', $exception->getMessage());
        self::assertSame(3, $exception->getCode());
        self::assertSame($previous, $exception->getPrevious());
        self::assertSame(['path' => '/users'], $exception->context);
    }

    #[Test]
    public function it_merges_what_is_added_to_its_context(): void
    {
        $exception = new RouteNotFoundException(context: ['path' => '/users', 'kept' => true]);

        self::assertSame($exception, $exception->addContext(['path' => '/posts', 'host' => 'example.com']));
        self::assertSame(['path' => '/posts', 'kept' => true, 'host' => 'example.com'], $exception->context);
    }

    #[Test]
    public function it_describes_a_path_without_a_route(): void
    {
        $exception = RouteNotFoundException::forPath('GET', "/users\n");

        self::assertSame('No route matches GET "/users\n".', $exception->getMessage());
        self::assertSame(['method' => 'GET', 'path' => "/users\n"], $exception->context);
    }

    #[Test]
    public function it_describes_an_unknown_name(): void
    {
        $exception = RouteNotFoundException::forName(RouteName::UsersShow);

        self::assertSame('No route is named "' . RouteName::class . '::UsersShow".', $exception->getMessage());
        self::assertSame(['name' => RouteName::UsersShow], $exception->context);
    }
}
