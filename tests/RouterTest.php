<?php

declare(strict_types=1);

namespace Dirthara\Routing\Tests;

use Closure;
use Dirthara\Routing\Route;
use Dirthara\Routing\Router;
use PHPUnit\Framework\TestCase;
use Dirthara\Routing\HttpMethod;
use Dirthara\Routing\RoutePattern;
use Dirthara\Routing\TrailingSlash;
use PHPUnit\Framework\Attributes\Test;
use Dirthara\Routing\Tests\Fixtures\RouteName;
use PHPUnit\Framework\Attributes\DataProvider;
use Dirthara\Routing\Exception\InvalidRouteException;
use Dirthara\Routing\Exception\RouteNotFoundException;
use Dirthara\Routing\Exception\MethodNotAllowedException;
use Dirthara\Routing\Exception\InvalidUrlParameterException;

final class RouterTest extends TestCase
{
    /**
     * @return iterable<string, array{HttpMethod, Closure(Router): Route}>
     */
    public static function shortcuts(): iterable
    {
        yield 'get' => [HttpMethod::Get, static fn(Router $router): Route => $router->get('/users', 'handler')];
        yield 'head' => [HttpMethod::Head, static fn(Router $router): Route => $router->head('/users', 'handler')];
        yield 'post' => [HttpMethod::Post, static fn(Router $router): Route => $router->post('/users', 'handler')];
        yield 'put' => [HttpMethod::Put, static fn(Router $router): Route => $router->put('/users', 'handler')];
        yield 'patch' => [HttpMethod::Patch, static fn(Router $router): Route => $router->patch('/users', 'handler')];
        yield 'delete' => [
            HttpMethod::Delete,
            static fn(Router $router): Route => $router->delete('/users', 'handler'),
        ];
        yield 'options' => [
            HttpMethod::Options,
            static fn(Router $router): Route => $router->options('/users', 'handler'),
        ];
    }

    /**
     * @param Closure(Router): Route $register
     */
    #[Test]
    #[DataProvider('shortcuts')]
    public function it_registers_a_route_for_each_method_shortcut(HttpMethod $method, Closure $register): void
    {
        $router = new Router();
        $route = $register($router);

        self::assertSame($method, $route->method);
        self::assertSame('/users', $route->path);
        self::assertSame('handler', $route->handler());
        self::assertSame($route, $router->match($method, '/users')->route);
    }

    #[Test]
    public function it_ignores_trailing_slashes_by_default(): void
    {
        $router = new Router();

        self::assertSame(TrailingSlash::Ignore, $router->trailingSlash);
    }

    #[Test]
    public function it_matches_a_static_route(): void
    {
        $router = new Router();
        $route = $router->add(HttpMethod::Get, '/users', 'users.index');

        $match = $router->match(HttpMethod::Get, '/users');

        self::assertSame($route, $match->route);
        self::assertSame([], $match->parameters);
        self::assertSame('users.index', $match->handler());
    }

    #[Test]
    public function it_matches_a_method_given_as_a_string(): void
    {
        $router = new Router();
        $route = $router->post('/users', 'users.store');

        self::assertSame($route, $router->match('POST', '/users')->route);
    }

    #[Test]
    public function it_treats_method_names_as_case_sensitive(): void
    {
        $router = new Router();
        $router->get('/users', 'users.index');

        $this->expectException(MethodNotAllowedException::class);

        $router->match('get', '/users');
    }

    #[Test]
    public function it_extracts_and_decodes_parameters(): void
    {
        $router = new Router();
        $router->get('/users/{user}/posts/{post}', 'posts.show');

        $match = $router->match(HttpMethod::Get, '/users/j%C3%B6rg/posts/hello%20world');

        self::assertSame(['user' => 'jörg', 'post' => 'hello world'], $match->parameters);
    }

    #[Test]
    public function it_does_not_match_a_parameter_across_segments(): void
    {
        $router = new Router();
        $router->get('/files/{name}', 'files.show');

        $this->expectException(RouteNotFoundException::class);

        $router->match(HttpMethod::Get, '/files/a/b');
    }

    #[Test]
    public function it_applies_constraints_when_matching(): void
    {
        $router = new Router();
        $numeric = $router->get('/users/{id}', 'users.show')->where('id', RoutePattern::Integer);
        $slug = $router->get('/users/{slug}', 'users.profile');

        self::assertSame($numeric, $router->match(HttpMethod::Get, '/users/42')->route);
        self::assertSame($slug, $router->match(HttpMethod::Get, '/users/jane')->route);
    }

    #[Test]
    public function it_returns_the_first_registered_route_that_matches(): void
    {
        $router = new Router();
        $first = $router->get('/users/{id}', 'first');
        $router->get('/users/{name}', 'second');

        self::assertSame($first, $router->match(HttpMethod::Get, '/users/1')->route);
    }

    #[Test]
    public function it_matches_an_empty_path_as_the_root(): void
    {
        $router = new Router();
        $route = $router->get('/', 'home');

        self::assertSame($route, $router->match(HttpMethod::Get, '')->route);
    }

    #[Test]
    public function it_matches_with_or_without_a_trailing_slash_when_ignoring_it(): void
    {
        $router = new Router();
        $users = $router->get('/users', 'users.index');
        $posts = $router->get('/posts/', 'posts.index');
        $root = $router->get('/', 'home');

        self::assertSame($users, $router->match(HttpMethod::Get, '/users/')->route);
        self::assertSame($users, $router->match(HttpMethod::Get, '/users')->route);
        self::assertSame($posts, $router->match(HttpMethod::Get, '/posts')->route);
        self::assertSame($posts, $router->match(HttpMethod::Get, '/posts/')->route);
        self::assertSame($root, $router->match(HttpMethod::Get, '/')->route);
    }

    #[Test]
    public function it_requires_the_exact_trailing_slash_when_strict(): void
    {
        $router = new Router(TrailingSlash::Strict);
        $users = $router->get('/users', 'users.index');
        $posts = $router->get('/posts/', 'posts.index');

        self::assertSame($users, $router->match(HttpMethod::Get, '/users')->route);
        self::assertSame($posts, $router->match(HttpMethod::Get, '/posts/')->route);

        $this->expectException(RouteNotFoundException::class);

        $router->match(HttpMethod::Get, '/users/');
    }

    #[Test]
    public function it_answers_head_with_a_get_route(): void
    {
        $router = new Router();
        $route = $router->get('/users', 'users.index');

        self::assertSame($route, $router->match(HttpMethod::Head, '/users')->route);
        self::assertSame($route, $router->match('HEAD', '/users')->route);
    }

    #[Test]
    public function it_prefers_an_explicit_head_route_registered_after_the_get_route(): void
    {
        $router = new Router();
        $router->get('/users', 'users.index');
        $head = $router->head('/users', 'users.head');

        self::assertSame($head, $router->match(HttpMethod::Head, '/users')->route);
    }

    #[Test]
    public function it_throws_when_no_route_matches_the_path(): void
    {
        $router = new Router();
        $router->get('/users', 'users.index');

        try {
            $router->match(HttpMethod::Get, '/posts');
            self::fail('Expected a RouteNotFoundException.');
        } catch (RouteNotFoundException $exception) {
            self::assertSame(['method' => 'GET', 'path' => '/posts'], $exception->context);
        }
    }

    #[Test]
    public function it_throws_with_the_allowed_methods_when_only_the_method_does_not_match(): void
    {
        $router = new Router();
        $router->get('/users', 'users.index');
        $router->post('/users', 'users.store');
        $router->post('/users/{id}', 'users.update');
        $router->delete('/users', 'users.clear');
        $router->get('/users', 'users.duplicate');

        try {
            $router->match(HttpMethod::Put, '/users');
            self::fail('Expected a MethodNotAllowedException.');
        } catch (MethodNotAllowedException $exception) {
            self::assertSame(['GET', 'HEAD', 'POST', 'DELETE'], $exception->allowedMethods);
            self::assertSame('PUT', $exception->context['method']);
        }
    }

    #[Test]
    public function it_reports_an_unknown_method_as_not_allowed(): void
    {
        $router = new Router();
        $router->post('/users', 'users.store');

        try {
            $router->match('PROPFIND', '/users');
            self::fail('Expected a MethodNotAllowedException.');
        } catch (MethodNotAllowedException $exception) {
            self::assertSame(['POST'], $exception->allowedMethods);
            self::assertSame('PROPFIND', $exception->context['method']);
        }
    }

    #[Test]
    public function it_rejects_an_invalid_route_path(): void
    {
        $router = new Router();

        $this->expectException(InvalidRouteException::class);

        $router->get('users', 'users.index');
    }

    #[Test]
    public function it_generates_a_url_for_a_named_route(): void
    {
        $router = new Router();
        $router->get('/users/{id}/posts/{slug}', 'posts.show')->name('posts.show');

        self::assertSame('/users/7/posts/hello-world', $router->url('posts.show', [
            'id' => 7,
            'slug' => 'hello-world',
        ]));
    }

    #[Test]
    public function it_generates_a_url_for_a_route_named_by_an_enum(): void
    {
        $router = new Router();
        $router->get('/users', 'users.index')->name(RouteName::UsersIndex);
        $router->get('/other', 'other')->name('UsersIndex');

        self::assertSame('/users', $router->url(RouteName::UsersIndex));
        self::assertSame('/other', $router->url('UsersIndex'));
    }

    #[Test]
    public function it_generates_a_url_that_matches_the_same_route(): void
    {
        $router = new Router();
        $route = $router->get('/files/{name}', 'files.show')->name('files.show');

        $url = $router->url('files.show', ['name' => 'annual report/2026.pdf']);
        $match = $router->match(HttpMethod::Get, $url);

        self::assertSame('/files/annual%20report%2F2026.pdf', $url);
        self::assertSame($route, $match->route);
        self::assertSame(['name' => 'annual report/2026.pdf'], $match->parameters);
    }

    #[Test]
    public function it_throws_when_generating_a_url_for_an_unknown_name(): void
    {
        $router = new Router();

        $this->expectException(RouteNotFoundException::class);
        $this->expectExceptionMessageIs('No route is named "users.index".');

        $router->url('users.index');
    }

    #[Test]
    public function it_throws_when_generating_a_url_for_a_name_used_twice(): void
    {
        $router = new Router();
        $router->get('/users', 'first')->name('users');
        $router->get('/people', 'second')->name('users');

        $this->expectException(InvalidRouteException::class);

        $router->url('users');
    }

    #[Test]
    public function it_throws_when_a_url_parameter_is_invalid(): void
    {
        $router = new Router();
        $router->get('/users/{id}', 'users.show')->name('users.show')->where('id', RoutePattern::Integer);

        $this->expectException(InvalidUrlParameterException::class);

        $router->url('users.show', ['id' => 'jane']);
    }

    #[Test]
    public function it_matches_a_route_with_or_without_its_optional_parameter(): void
    {
        $router = new Router();
        $route = $router->get('/posts/{page?}', 'posts.index')->where('page', RoutePattern::Integer);

        self::assertSame([], $router->match(HttpMethod::Get, '/posts')->parameters);
        self::assertSame([], $router->match(HttpMethod::Get, '/posts/')->parameters);
        self::assertSame(['page' => '2'], $router->match(HttpMethod::Get, '/posts/2')->parameters);
        self::assertSame(['page' => '2'], $router->match(HttpMethod::Get, '/posts/2/')->parameters);
        self::assertSame($route, $router->match(HttpMethod::Get, '/posts')->route);
    }

    #[Test]
    public function it_does_not_match_an_optional_parameter_that_breaks_its_constraint(): void
    {
        $router = new Router();
        $router->get('/posts/{page?}', 'posts.index')->where('page', RoutePattern::Integer);

        $this->expectException(RouteNotFoundException::class);

        $router->match(HttpMethod::Get, '/posts/last');
    }

    #[Test]
    public function it_matches_an_optional_parameter_strictly(): void
    {
        $router = new Router(TrailingSlash::Strict);
        $route = $router->get('/posts/{page?}', 'posts.index');

        self::assertSame($route, $router->match(HttpMethod::Get, '/posts')->route);
        self::assertSame(['page' => '2'], $router->match(HttpMethod::Get, '/posts/2')->parameters);

        $this->expectException(RouteNotFoundException::class);

        $router->match(HttpMethod::Get, '/posts/');
    }

    #[Test]
    public function it_matches_an_optional_parameter_at_the_root(): void
    {
        foreach ([TrailingSlash::Ignore, TrailingSlash::Strict] as $trailingSlash) {
            $router = new Router($trailingSlash);
            $router->get('/{page?}', 'home');

            self::assertSame([], $router->match(HttpMethod::Get, '/')->parameters);
            self::assertSame([], $router->match(HttpMethod::Get, '')->parameters);
            self::assertSame(['page' => '2'], $router->match(HttpMethod::Get, '/2')->parameters);
        }
    }

    #[Test]
    public function it_generates_a_url_that_leaves_out_a_missing_optional_parameter(): void
    {
        $router = new Router();
        $router->get('/posts/{page?}', 'posts.index')->name('posts.index');
        $router->get('/{page?}', 'home')->name('home');

        self::assertSame('/posts', $router->url('posts.index'));
        self::assertSame('/posts/2', $router->url('posts.index', ['page' => 2]));
        self::assertSame('/', $router->url('home'));
    }

    #[Test]
    public function it_shares_its_url_generator(): void
    {
        $router = new Router();
        $router->get('/users/{id}', 'ShowUser')->name('users.show');

        self::assertSame($router->urls(), $router->urls());
        self::assertSame('/users/7', $router->urls()->action('ShowUser', ['id' => 7]));
        self::assertSame('/users/7', $router->urls()->name('users.show', ['id' => 7]));
    }

    #[Test]
    public function it_generates_urls_for_routes_added_after_the_generator_was_taken(): void
    {
        $router = new Router();
        $urls = $router->urls();

        $router->get('/users', 'ListUsers');

        self::assertSame('/users', $urls->action('ListUsers'));
    }
}
