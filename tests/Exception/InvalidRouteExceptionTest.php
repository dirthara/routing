<?php

declare(strict_types=1);

namespace Dirthara\Routing\Tests\Exception;

use RuntimeException;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Dirthara\Routing\HttpMethod;
use PHPUnit\Framework\Attributes\Test;
use Dirthara\Routing\Exception\RoutingException;
use Dirthara\Routing\Tests\Fixtures\UserController;
use Dirthara\Routing\Exception\InvalidRouteException;

final class InvalidRouteExceptionTest extends TestCase
{
    #[Test]
    public function it_carries_nothing_by_default(): void
    {
        $exception = new InvalidRouteException();

        self::assertInstanceOf(RoutingException::class, $exception);
        self::assertInstanceOf(InvalidArgumentException::class, $exception);
        self::assertSame('', $exception->getMessage());
        self::assertSame(0, $exception->getCode());
        self::assertNull($exception->getPrevious());
        self::assertSame([], $exception->context);
    }

    #[Test]
    public function it_keeps_a_previous_exception_and_its_context(): void
    {
        $previous = new RuntimeException('cause');
        $exception = new InvalidRouteException('message', 3, $previous, ['path' => '/users']);

        self::assertSame('message', $exception->getMessage());
        self::assertSame(3, $exception->getCode());
        self::assertSame($previous, $exception->getPrevious());
        self::assertSame(['path' => '/users'], $exception->context);
    }

    #[Test]
    public function it_merges_what_is_added_to_its_context(): void
    {
        $exception = new InvalidRouteException(context: ['path' => '/users', 'kept' => true]);

        self::assertSame($exception, $exception->addContext(['path' => '/posts', 'router' => 'api']));
        self::assertSame(['path' => '/posts', 'kept' => true, 'router' => 'api'], $exception->context);
    }

    #[Test]
    public function it_describes_an_invalid_pattern(): void
    {
        $exception = InvalidRouteException::invalidPattern('/users/{id}', 'id', "[0-9\t");

        self::assertSame(
            'The pattern "[0-9\t" for the parameter "id" of the route path "/users/{id}" is not a valid regular '
            . 'expression.',
            $exception->getMessage(),
        );
        self::assertSame(['path' => '/users/{id}', 'parameter' => 'id', 'pattern' => "[0-9\t"], $exception->context);
    }

    #[Test]
    public function it_describes_an_unknown_parameter(): void
    {
        $exception = InvalidRouteException::unknownParameter('/users/{id}', 'user');

        self::assertSame(
            'Cannot constrain "user": the route path "/users/{id}" has no such parameter.',
            $exception->getMessage(),
        );
    }

    #[Test]
    public function it_describes_a_misplaced_optional_parameter(): void
    {
        $exception = InvalidRouteException::misplacedOptionalParameter("/{lang?}/about\n", 'lang');

        self::assertSame(
            'The optional parameter "lang" of the route path "/{lang?}/about\\n" must be the whole last segment of '
            . 'the path, as in "/posts/{page?}".',
            $exception->getMessage(),
        );
        self::assertSame(['path' => "/{lang?}/about\n", 'parameter' => 'lang'], $exception->context);
    }

    #[Test]
    public function it_describes_an_ambiguous_action(): void
    {
        $exception = InvalidRouteException::ambiguousAction(
            [UserController::class, 'show'],
            HttpMethod::Get,
            ["GET /users/{id}\n", 'GET /people/{id}'],
        );

        self::assertSame(
            'The action "'
            . UserController::class
            . '::show" is the handler of more than one GET route: GET /users/{id}\\n, GET /people/{id}.',
            $exception->getMessage(),
        );
        self::assertSame(
            [
                'action' => [UserController::class, 'show'],
                'method' => 'GET',
                'routes' => ["GET /users/{id}\n", 'GET /people/{id}'],
            ],
            $exception->context,
        );
    }
}
