<?php

declare(strict_types=1);

namespace Dirthara\Routing\Tests;

use Dirthara\Routing\Route;
use PHPUnit\Framework\TestCase;
use Dirthara\Routing\HttpMethod;
use Dirthara\Routing\RoutePattern;
use Dirthara\Routing\UrlGenerator;
use Dirthara\Routing\RouteCollection;
use PHPUnit\Framework\Attributes\Test;
use Dirthara\Routing\Tests\Fixtures\RouteName;
use Dirthara\Routing\Tests\Fixtures\UserController;
use Dirthara\Routing\Exception\InvalidRouteException;
use Dirthara\Routing\Exception\RouteNotFoundException;
use Dirthara\Routing\Exception\InvalidUrlParameterException;

final class UrlGeneratorTest extends TestCase
{
    #[Test]
    public function it_generates_a_path_by_name(): void
    {
        $routes = new RouteCollection();
        $routes->add(new Route(HttpMethod::Get, '/users/{id}', 'users.show')->name('users.show'));
        $routes->add(new Route(HttpMethod::Get, '/', 'home')->name(RouteName::UsersIndex));
        $urls = new UrlGenerator($routes);

        self::assertSame('/users/7', $urls->name('users.show', ['id' => 7]));
        self::assertSame('/', $urls->name(RouteName::UsersIndex));
    }

    #[Test]
    public function it_throws_for_an_unknown_name(): void
    {
        $urls = new UrlGenerator(new RouteCollection());

        $this->expectException(RouteNotFoundException::class);
        $this->expectExceptionMessageIs('No route is named "users.show".');

        $urls->name('users.show');
    }

    #[Test]
    public function it_generates_a_path_by_a_class_name_action(): void
    {
        $routes = new RouteCollection();
        $routes->add(new Route(HttpMethod::Get, '/users', 'ListUsers'));
        $routes->add(new Route(HttpMethod::Get, '/users/{id}', 'ShowUser'));
        $urls = new UrlGenerator($routes);

        self::assertSame('/users/7', $urls->action('ShowUser', ['id' => 7]));
        self::assertSame('/users', $urls->action('ListUsers'));
    }

    #[Test]
    public function it_generates_a_path_by_a_controller_method_action(): void
    {
        $routes = new RouteCollection();
        $routes->add(new Route(HttpMethod::Get, '/users', [UserController::class, 'index']));
        $routes->add(new Route(HttpMethod::Get, '/users/{id}', [UserController::class, 'show']));
        $urls = new UrlGenerator($routes);

        self::assertSame('/users/7', $urls->action([UserController::class, 'show'], ['id' => 7]));
    }

    #[Test]
    public function it_compares_actions_strictly(): void
    {
        $handler = static fn(): string => 'response';
        $routes = new RouteCollection();
        $routes->add(new Route(HttpMethod::Get, '/one', 1));
        $routes->add(new Route(HttpMethod::Get, '/closure', $handler));
        $urls = new UrlGenerator($routes);

        self::assertSame('/closure', $urls->action($handler));

        $this->expectException(RouteNotFoundException::class);

        $urls->action('1');
    }

    #[Test]
    public function it_cannot_find_a_route_by_an_equal_but_different_closure(): void
    {
        $routes = new RouteCollection();
        $routes->add(new Route(HttpMethod::Get, '/closure', static fn(): string => 'response'));
        $urls = new UrlGenerator($routes);

        $this->expectException(RouteNotFoundException::class);
        $this->expectExceptionMessageIs('No route has the action "Closure".');

        $urls->action(static fn(): string => 'response');
    }

    #[Test]
    public function it_narrows_an_action_by_method(): void
    {
        $routes = new RouteCollection();
        $routes->add(new Route(HttpMethod::Get, '/users/{id}/edit', [UserController::class, 'show']));
        $routes->add(new Route(HttpMethod::Put, '/users/{id}', [UserController::class, 'show']));
        $urls = new UrlGenerator($routes);

        self::assertSame('/users/3/edit', $urls->action([UserController::class, 'show'], ['id' => 3], HttpMethod::Get));
        self::assertSame('/users/3', $urls->action([UserController::class, 'show'], ['id' => 3], HttpMethod::Put));
    }

    #[Test]
    public function it_throws_for_an_ambiguous_action(): void
    {
        $routes = new RouteCollection();
        $routes->add(new Route(HttpMethod::Get, '/users/{id}/edit', [UserController::class, 'show']));
        $routes->add(new Route(HttpMethod::Put, '/users/{id}', [UserController::class, 'show']));
        $urls = new UrlGenerator($routes);

        $this->expectException(InvalidRouteException::class);
        $this->expectExceptionMessageIs(
            'The action "'
            . UserController::class
            . '::show" is the handler of more than one route: GET /users/{id}/edit, PUT /users/{id}. Pass a method to '
            . 'choose one.',
        );

        $urls->action([UserController::class, 'show'], ['id' => 3]);
    }

    #[Test]
    public function it_throws_for_an_action_that_is_still_ambiguous_with_a_method(): void
    {
        $routes = new RouteCollection();
        $routes->add(new Route(HttpMethod::Get, '/posts', 'ListPosts'));
        $routes->add(new Route(HttpMethod::Get, '/posts/{page}', 'ListPosts'));
        $routes->add(new Route(HttpMethod::Post, '/posts', 'ListPosts'));
        $urls = new UrlGenerator($routes);

        $this->expectException(InvalidRouteException::class);
        $this->expectExceptionMessageIs(
            'The action "ListPosts" is the handler of more than one GET route: GET /posts, GET /posts/{page}.',
        );

        $urls->action('ListPosts', method: HttpMethod::Get);
    }

    #[Test]
    public function it_throws_for_an_action_without_a_route_for_the_method(): void
    {
        $routes = new RouteCollection();
        $routes->add(new Route(HttpMethod::Get, '/users', 'ListUsers'));
        $urls = new UrlGenerator($routes);

        $this->expectException(RouteNotFoundException::class);
        $this->expectExceptionMessageIs('No POST route has the action "ListUsers".');

        $urls->action('ListUsers', method: HttpMethod::Post);
    }

    #[Test]
    public function it_validates_parameters_when_generating_by_action(): void
    {
        $routes = new RouteCollection();
        $routes->add(new Route(HttpMethod::Get, '/users/{id}', 'ShowUser')->where('id', RoutePattern::Integer));
        $urls = new UrlGenerator($routes);

        $this->expectException(InvalidUrlParameterException::class);

        $urls->action('ShowUser', ['id' => 'jane']);
    }

    #[Test]
    public function it_leaves_out_a_missing_optional_parameter_when_generating_by_action(): void
    {
        $routes = new RouteCollection();
        $routes->add(new Route(HttpMethod::Get, '/posts/{page?}', 'ListPosts'));
        $urls = new UrlGenerator($routes);

        self::assertSame('/posts', $urls->action('ListPosts'));
        self::assertSame('/posts/2', $urls->action('ListPosts', ['page' => 2]));
    }
}
