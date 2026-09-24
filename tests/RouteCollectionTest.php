<?php

declare(strict_types=1);

namespace Dirthara\Routing\Tests;

use Dirthara\Routing\Route;
use PHPUnit\Framework\TestCase;
use Dirthara\Routing\HttpMethod;
use Dirthara\Routing\RouteCollection;
use PHPUnit\Framework\Attributes\Test;
use Dirthara\Routing\Tests\Fixtures\RouteName;
use Dirthara\Routing\Exception\InvalidRouteException;

use function iterator_to_array;

final class RouteCollectionTest extends TestCase
{
    #[Test]
    public function it_keeps_routes_in_the_order_they_were_added(): void
    {
        $collection = new RouteCollection();
        $first = new Route(HttpMethod::Get, '/a', 'a');
        $second = new Route(HttpMethod::Get, '/b', 'b');

        $collection->add($first);
        $collection->add($second);

        self::assertCount(2, $collection);
        self::assertSame([$first, $second], iterator_to_array($collection));
    }

    #[Test]
    public function it_finds_a_route_by_name(): void
    {
        $collection = new RouteCollection();
        $users = new Route(HttpMethod::Get, '/users', 'users')->name('users');
        $posts = new Route(HttpMethod::Get, '/posts', 'posts')->name(RouteName::UsersShow);
        $collection->add($users);
        $collection->add($posts);
        $collection->add(new Route(HttpMethod::Get, '/unnamed', 'unnamed'));

        self::assertSame($users, $collection->named('users'));
        self::assertSame($posts, $collection->named(RouteName::UsersShow));
        self::assertNull($collection->named(RouteName::UsersIndex));
        self::assertNull($collection->named('UsersShow'));
    }

    #[Test]
    public function it_finds_a_route_that_was_named_after_it_was_added(): void
    {
        $collection = new RouteCollection();
        $route = new Route(HttpMethod::Get, '/users', 'users');
        $collection->add($route);

        $route->name('users');

        self::assertSame($route, $collection->named('users'));
    }

    #[Test]
    public function it_rejects_a_name_given_to_two_routes(): void
    {
        $collection = new RouteCollection();
        $collection->add(new Route(HttpMethod::Get, '/users', 'first')->name(RouteName::UsersIndex));
        $collection->add(new Route(HttpMethod::Post, '/people', 'second')->name(RouteName::UsersIndex));

        try {
            $collection->named(RouteName::UsersIndex);
            self::fail('Expected an InvalidRouteException.');
        } catch (InvalidRouteException $exception) {
            self::assertSame(
                'The name "'
                . RouteName::class
                . '::UsersIndex" is given to more than one route: "/users" and '
                . '"/people".',
                $exception->getMessage(),
            );
            self::assertSame(['name' => RouteName::UsersIndex, 'paths' => ['/users', '/people']], $exception->context);
        }
    }
}
