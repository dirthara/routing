<?php

declare(strict_types=1);

namespace Dirthara\Routing\Tests;

use Stringable;
use Dirthara\Routing\Route;
use PHPUnit\Framework\TestCase;
use Dirthara\Routing\HttpMethod;
use Dirthara\Routing\RoutePattern;
use Dirthara\Routing\TrailingSlash;
use PHPUnit\Framework\Attributes\Test;
use Dirthara\Routing\Tests\Fixtures\RouteName;
use Dirthara\Routing\Exception\InvalidRouteException;
use Dirthara\Routing\Exception\InvalidUrlParameterException;

final class RouteTest extends TestCase
{
    #[Test]
    public function it_exposes_its_definition(): void
    {
        $handler = static fn(): string => 'response';
        $route = new Route(HttpMethod::Post, '/users/{id}', $handler);

        self::assertSame(HttpMethod::Post, $route->method);
        self::assertSame('/users/{id}', $route->path);
        self::assertSame($handler, $route->handler());
        self::assertNull($route->name);
        self::assertSame(['id'], $route->parameters());
        self::assertSame([], $route->constraints());
    }

    #[Test]
    public function it_takes_a_string_or_enum_name(): void
    {
        $route = new Route(HttpMethod::Get, '/users', 'handler');

        self::assertSame($route, $route->name('users.index'));
        self::assertSame('users.index', $route->name);
        self::assertSame(RouteName::UsersIndex, $route->name(RouteName::UsersIndex)->name);
    }

    #[Test]
    public function it_rejects_an_empty_name(): void
    {
        $route = new Route(HttpMethod::Get, '/users', 'handler');

        $this->expectException(InvalidRouteException::class);
        $this->expectExceptionMessageIs('The route "/users" cannot be given an empty name.');

        $route->name('');
    }

    #[Test]
    public function it_stores_constraints_as_patterns(): void
    {
        $route = new Route(HttpMethod::Get, '/users/{id}/{slug}', 'handler');

        self::assertSame($route, $route->where('id', RoutePattern::Integer));
        self::assertSame($route, $route->where('slug', '[a-z]+'));
        self::assertSame(['id' => '\d+', 'slug' => '[a-z]+'], $route->constraints());
    }

    #[Test]
    public function it_stores_many_constraints_at_once(): void
    {
        $route = new Route(HttpMethod::Get, '/users/{id}/{slug}', 'handler');

        self::assertSame($route, $route->whereMany(['id' => RoutePattern::Integer, 'slug' => RoutePattern::Slug]));
        self::assertSame(['id' => '\d+', 'slug' => RoutePattern::Slug->value], $route->constraints());
    }

    #[Test]
    public function it_replaces_an_earlier_constraint(): void
    {
        $route = new Route(HttpMethod::Get, '/users/{id}', 'handler');

        $route->where('id', RoutePattern::Integer)->where('id', RoutePattern::Uuid);

        self::assertSame(['id' => RoutePattern::Uuid->value], $route->constraints());
    }

    #[Test]
    public function it_rejects_a_constraint_for_an_unknown_parameter(): void
    {
        $route = new Route(HttpMethod::Get, '/users/{id}', 'handler');

        try {
            $route->where('user', RoutePattern::Integer);
            self::fail('Expected an InvalidRouteException.');
        } catch (InvalidRouteException $exception) {
            self::assertSame(['path' => '/users/{id}', 'parameter' => 'user'], $exception->context);
        }
    }

    #[Test]
    public function it_rejects_an_invalid_regular_expression(): void
    {
        $route = new Route(HttpMethod::Get, '/users/{id}', 'handler');

        try {
            $route->where('id', '[0-9');
            self::fail('Expected an InvalidRouteException.');
        } catch (InvalidRouteException $exception) {
            self::assertSame('[0-9', $exception->context['pattern']);
            self::assertSame([], $route->constraints());
        }
    }

    #[Test]
    public function it_rejects_a_pattern_that_contains_the_delimiter(): void
    {
        $route = new Route(HttpMethod::Get, '/users/{id}', 'handler');

        $this->expectException(InvalidRouteException::class);

        $route->where('id', 'a~b');
    }

    #[Test]
    public function it_accepts_an_escaped_delimiter_in_a_pattern(): void
    {
        $route = new Route(HttpMethod::Get, '/users/{id}', 'handler')->where('id', 'a\~b');

        self::assertSame(['id' => 'a~b'], $route->matches('/users/a~b'));
    }

    #[Test]
    public function it_returns_the_parameters_of_a_matching_path(): void
    {
        $route = new Route(HttpMethod::Get, '/users/{id}', 'handler');

        self::assertSame(['id' => '5'], $route->matches('/users/5'));
        self::assertNull($route->matches('/posts/5'));
    }

    #[Test]
    public function it_anchors_a_constraint_with_alternatives_to_the_whole_parameter(): void
    {
        $route = new Route(HttpMethod::Get, '/reports/{format}', 'handler');
        $route->where('format', 'csv|json');

        self::assertSame(['format' => 'json'], $route->matches('/reports/json'));
        self::assertNull($route->matches('/reports/jsonx'));
        self::assertNull($route->matches('/reports/xcsv'));
    }

    #[Test]
    public function it_does_not_match_a_path_with_a_trailing_newline(): void
    {
        $route = new Route(HttpMethod::Get, '/users', 'handler');

        self::assertNull($route->matches("/users\n", TrailingSlash::Strict));
    }

    #[Test]
    public function it_generates_a_path_from_its_parameters(): void
    {
        $route = new Route(HttpMethod::Get, '/users/{id}/{slug}', 'handler');
        $slug = new class implements Stringable {
            public function __toString(): string
            {
                return 'jane doe';
            }
        };

        self::assertSame('/users/3/jane%20doe', $route->generatePath(['id' => 3, 'slug' => $slug]));
        self::assertSame('/users/1.5/0', $route->generatePath(['id' => 1.5, 'slug' => false]));
        self::assertSame('/users/1/x', $route->generatePath(['slug' => 'x', 'id' => true]));
    }

    #[Test]
    public function it_generates_a_path_without_parameters(): void
    {
        $route = new Route(HttpMethod::Get, '/users', 'handler');

        self::assertSame('/users', $route->generatePath());
    }

    #[Test]
    public function it_rejects_a_missing_url_parameter(): void
    {
        $route = new Route(HttpMethod::Get, '/users/{id}', 'handler');

        $this->expectException(InvalidUrlParameterException::class);
        $this->expectExceptionMessageIs(
            'Cannot generate a URL for the route "/users/{id}": the parameter "id" is missing.',
        );

        $route->generatePath();
    }

    #[Test]
    public function it_rejects_unknown_url_parameters(): void
    {
        $route = new Route(HttpMethod::Get, '/users/{id}', 'handler')->name('users.show');

        try {
            $route->generatePath(['id' => 1, 'page' => 2, 'sort' => 'name']);
            self::fail('Expected an InvalidUrlParameterException.');
        } catch (InvalidUrlParameterException $exception) {
            self::assertSame(['page', 'sort'], $exception->context['parameters']);
            self::assertSame(
                'Cannot generate a URL for the route "/users/{id}" named "users.show": it has no parameter named '
                . '"page", "sort".',
                $exception->getMessage(),
            );
        }
    }

    #[Test]
    public function it_rejects_a_url_parameter_that_does_not_match_its_constraint(): void
    {
        $route = new Route(HttpMethod::Get, '/users/{id}', 'handler')->where('id', RoutePattern::Integer);

        $this->expectException(InvalidUrlParameterException::class);

        $route->generatePath(['id' => '12a']);
    }

    #[Test]
    public function it_rejects_an_empty_url_parameter(): void
    {
        $route = new Route(HttpMethod::Get, '/users/{id}', 'handler');

        $this->expectException(InvalidUrlParameterException::class);

        $route->generatePath(['id' => '']);
    }

    #[Test]
    public function it_generates_a_path_without_its_optional_parameter(): void
    {
        $route = new Route(HttpMethod::Get, '/users/{user}/posts/{page?}', 'handler');

        self::assertSame('/users/jane/posts', $route->generatePath(['user' => 'jane']));
        self::assertSame('/users/jane/posts/2', $route->generatePath(['user' => 'jane', 'page' => 2]));
    }

    #[Test]
    public function it_still_requires_the_other_parameters_of_a_route_with_an_optional_one(): void
    {
        $route = new Route(HttpMethod::Get, '/users/{user}/posts/{page?}', 'handler');

        $this->expectException(InvalidUrlParameterException::class);
        $this->expectExceptionMessageIs(
            'Cannot generate a URL for the route "/users/{user}/posts/{page?}": the parameter "user" is missing.',
        );

        $route->generatePath(['page' => 2]);
    }

    #[Test]
    public function it_checks_a_given_optional_parameter_against_its_constraint(): void
    {
        $route = new Route(HttpMethod::Get, '/posts/{page?}', 'handler')->where('page', RoutePattern::Integer);

        $this->expectException(InvalidUrlParameterException::class);

        $route->generatePath(['page' => 'last']);
    }

    #[Test]
    public function it_resets_a_constraint_to_the_default_segment_pattern(): void
    {
        $route = new Route(HttpMethod::Get, '/users/{id}', 'handler')->where('id', RoutePattern::Integer);

        self::assertNull($route->matches('/users/jane'));

        $route->where('id', RoutePattern::Segment);

        self::assertSame(['id' => 'jane'], $route->matches('/users/jane'));
        self::assertSame(['id' => '[^/]+'], $route->constraints());
    }
}
