<?php

declare(strict_types=1);

namespace Dirthara\Routing\Tests;

use Dirthara\Routing\RoutePath;
use PHPUnit\Framework\TestCase;
use Dirthara\Routing\TrailingSlash;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\DataProvider;
use Dirthara\Routing\Exception\InvalidRouteException;

use function sprintf;

final class RoutePathTest extends TestCase
{
    /**
     * @return iterable<string, array{string, string}>
     */
    public static function invalidPaths(): iterable
    {
        yield 'empty' => ['', 'The route path "" must start with a "/".'];
        yield 'relative' => ['users/{id}', 'The route path "users/{id}" must start with a "/".'];
        yield 'unclosed brace' => [
            '/users/{id',
            'The route path "/users/{id" has a "{" or "}" outside a {parameter} placeholder.',
        ];
        yield 'unopened brace' => [
            '/users/id}',
            'The route path "/users/id}" has a "{" or "}" outside a {parameter} placeholder.',
        ];
        yield 'nested braces' => [
            '/users/{a{b}}',
            'The route path "/users/{a{b}}" has a "{" or "}" outside a {parameter} placeholder.',
        ];
        yield 'empty parameter' => ['/users/{}', self::invalidNameMessage('/users/{}', '')];
        yield 'leading digit' => ['/users/{1d}', self::invalidNameMessage('/users/{1d}', '1d')];
        yield 'dash' => ['/users/{user-id}', self::invalidNameMessage('/users/{user-id}', 'user-id')];
        yield 'too long' => [
            '/{a234567890123456789012345678901234}',
            self::invalidNameMessage('/{a234567890123456789012345678901234}', 'a234567890123456789012345678901234'),
        ];
        yield 'duplicate' => ['/{id}/{id}', 'The route path "/{id}/{id}" uses the parameter "id" more than once.'];
        yield 'duplicate optional' => [
            '/{id}/{id?}',
            'The route path "/{id}/{id?}" uses the parameter "id" more than once.',
        ];
        yield 'invalid optional name' => ['/posts/{1?}', self::invalidNameMessage('/posts/{1?}', '1')];
        yield 'bare question mark' => ['/posts/{?}', self::invalidNameMessage('/posts/{?}', '')];
        yield 'optional before a segment' => ['/{lang?}/about', self::misplacedMessage('/{lang?}/about', 'lang')];
        yield 'optional before a parameter' => ['/{a?}/{b}', self::misplacedMessage('/{a?}/{b}', 'a')];
        yield 'optional with a suffix' => [
            '/posts/{page?}.json',
            self::misplacedMessage('/posts/{page?}.json', 'page'),
        ];
        yield 'optional with a prefix' => ['/posts/page{page?}', self::misplacedMessage('/posts/page{page?}', 'page')];
        yield 'optional after a parameter' => ['/{a}{b?}', self::misplacedMessage('/{a}{b?}', 'b')];
        yield 'optional with a trailing slash' => [
            '/posts/{page?}/',
            self::misplacedMessage('/posts/{page?}/', 'page'),
        ];
    }

    private static function misplacedMessage(string $path, string $parameter): string
    {
        return sprintf(
            'The optional parameter "%s" of the route path "%s" must be the whole last segment of the path, as in '
            . '"/posts/{page?}".',
            $parameter,
            $path,
        );
    }

    private static function invalidNameMessage(string $path, string $parameter): string
    {
        return sprintf(
            'The route path "%s" has an invalid parameter name "%s": it must start with a letter or underscore, contain '
            . 'only letters, digits, and underscores, and be at most 32 characters long.',
            $path,
            $parameter,
        );
    }

    #[Test]
    #[DataProvider('invalidPaths')]
    public function it_rejects_an_invalid_path(string $path, string $message): void
    {
        $this->expectException(InvalidRouteException::class);
        $this->expectExceptionMessageIs($message);

        new RoutePath($path);
    }

    #[Test]
    public function it_accepts_the_longest_parameter_name(): void
    {
        $path = new RoutePath('/{_a23456789012345678901234567890b}');

        self::assertSame(['_a23456789012345678901234567890b'], $path->parameters());
    }

    #[Test]
    public function it_lists_parameters_in_path_order(): void
    {
        $path = new RoutePath('/{b}/x/{a}{c}');

        self::assertSame('/{b}/x/{a}{c}', $path->path);
        self::assertSame(['b', 'a', 'c'], $path->parameters());
    }

    #[Test]
    public function it_quotes_literal_text_in_its_regex(): void
    {
        $path = new RoutePath('/a.b~c/{id}');

        self::assertSame('~^/a\.b\~c/(?P<id>\d+)$~D', $path->regex(['id' => '\d+'], TrailingSlash::Strict));
        self::assertSame('~^/a\.b\~c/(?P<id>\d+)/?$~D', $path->regex(['id' => '\d+'], TrailingSlash::Ignore));
    }

    #[Test]
    public function it_drops_one_trailing_slash_from_its_regex_when_ignoring_it(): void
    {
        self::assertSame('~^/users/?$~D', new RoutePath('/users/')->regex([], TrailingSlash::Ignore));
        self::assertSame('~^/$~D', new RoutePath('/')->regex([], TrailingSlash::Ignore));
    }

    #[Test]
    public function it_matches_adjacent_parameters(): void
    {
        $path = new RoutePath('/{name}.{format}');

        self::assertSame(
            ['name' => 'report', 'format' => 'pdf'],
            $path->match('/report.pdf', ['name' => '[^./]+', 'format' => '[a-z]+'], TrailingSlash::Strict),
        );
    }

    #[Test]
    public function it_does_not_decode_a_plus_as_a_space(): void
    {
        $path = new RoutePath('/search/{term}');

        self::assertSame(['term' => 'a+b'], $path->match('/search/a+b', ['term' => '[^/]+'], TrailingSlash::Strict));
    }

    #[Test]
    public function it_builds_a_path_from_encoded_values(): void
    {
        $path = new RoutePath('/users/{id}/{slug}');

        self::assertSame('/users/1/a%20b', $path->build(['id' => '1', 'slug' => 'a%20b']));
    }

    #[Test]
    public function it_knows_its_optional_parameter(): void
    {
        $path = new RoutePath('/users/{user}/posts/{page?}');

        self::assertSame('page', $path->optionalParameter);
        self::assertSame(['user', 'page'], $path->parameters());
        self::assertNull(new RoutePath('/users/{user}')->optionalParameter);
    }

    #[Test]
    public function it_makes_the_last_segment_optional_in_its_regex(): void
    {
        $path = new RoutePath('/posts/{page?}');
        $patterns = ['page' => '\d+'];

        self::assertSame('~^/posts(?:/(?P<page>\d+))?$~D', $path->regex($patterns, TrailingSlash::Strict));
        self::assertSame('~^/posts(?:/(?P<page>\d+))?/?$~D', $path->regex($patterns, TrailingSlash::Ignore));
    }

    #[Test]
    public function it_keeps_the_root_slash_in_the_regex_of_an_optional_root_parameter(): void
    {
        $path = new RoutePath('/{page?}');
        $patterns = ['page' => '\d+'];

        self::assertSame('~^/(?P<page>\d+)?$~D', $path->regex($patterns, TrailingSlash::Strict));
        self::assertSame('~^(?:/(?P<page>\d+))?/?$~D', $path->regex($patterns, TrailingSlash::Ignore));
    }

    #[Test]
    public function it_leaves_a_missing_optional_parameter_out_of_a_match(): void
    {
        $path = new RoutePath('/users/{user}/posts/{page?}');
        $patterns = ['user' => '[^/]+', 'page' => '[^/]+'];

        self::assertSame(['user' => 'jane'], $path->match('/users/jane/posts', $patterns, TrailingSlash::Strict));
        self::assertSame(
            ['user' => 'jane', 'page' => '2'],
            $path->match('/users/jane/posts/2', $patterns, TrailingSlash::Strict),
        );
    }

    #[Test]
    public function it_keeps_an_optional_parameter_that_matched_empty(): void
    {
        $path = new RoutePath('/posts/{page?}');

        self::assertSame(['page' => ''], $path->match('/posts/', ['page' => '\d*'], TrailingSlash::Strict));
    }

    #[Test]
    public function it_builds_a_path_with_or_without_its_optional_parameter(): void
    {
        $path = new RoutePath('/users/{user}/posts/{page?}');

        self::assertSame('/users/jane/posts/2', $path->build(['user' => 'jane', 'page' => '2']));
        self::assertSame('/users/jane/posts', $path->build(['user' => 'jane']));
        self::assertSame('/', new RoutePath('/{page?}')->build([]));
        self::assertSame('/3', new RoutePath('/{page?}')->build(['page' => '3']));
    }
}
