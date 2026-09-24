<?php

declare(strict_types=1);

namespace Dirthara\Routing\Tests\Exception;

use RuntimeException;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use Dirthara\Routing\Tests\Fixtures\RouteName;
use Dirthara\Routing\Exception\RoutingException;
use Dirthara\Routing\Exception\InvalidUrlParameterException;

final class InvalidUrlParameterExceptionTest extends TestCase
{
    #[Test]
    public function it_carries_nothing_by_default(): void
    {
        $exception = new InvalidUrlParameterException();

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
        $exception = new InvalidUrlParameterException('message', 3, $previous, ['path' => '/users']);

        self::assertSame('message', $exception->getMessage());
        self::assertSame(3, $exception->getCode());
        self::assertSame($previous, $exception->getPrevious());
        self::assertSame(['path' => '/users'], $exception->context);
    }

    #[Test]
    public function it_merges_what_is_added_to_its_context(): void
    {
        $exception = new InvalidUrlParameterException(context: ['path' => '/users', 'kept' => true]);

        self::assertSame($exception, $exception->addContext(['path' => '/posts', 'router' => 'api']));
        self::assertSame(['path' => '/posts', 'kept' => true, 'router' => 'api'], $exception->context);
    }

    #[Test]
    public function it_describes_a_value_that_does_not_match_without_the_value(): void
    {
        $exception = InvalidUrlParameterException::patternMismatch(RouteName::UsersShow, '/users/{id}', 'id', '\d+');

        self::assertSame(
            'Cannot generate a URL for the route "/users/{id}" named "'
            . RouteName::class
            . '::UsersShow": the value '
            . 'of "id" does not match its pattern "\d+".',
            $exception->getMessage(),
        );
        self::assertSame(
            ['name' => RouteName::UsersShow, 'path' => '/users/{id}', 'parameter' => 'id', 'pattern' => '\d+'],
            $exception->context,
        );
    }

    #[Test]
    public function it_describes_a_missing_parameter(): void
    {
        $exception = InvalidUrlParameterException::missingParameter('users.show', '/users/{id}', 'id');

        self::assertSame(
            'Cannot generate a URL for the route "/users/{id}" named "users.show": the parameter "id" is missing.',
            $exception->getMessage(),
        );
        self::assertSame(['name' => 'users.show', 'path' => '/users/{id}', 'parameter' => 'id'], $exception->context);
    }
}
