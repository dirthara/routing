<?php

declare(strict_types=1);

namespace Dirthara\Routing\Tests;

use PHPUnit\Framework\TestCase;
use Dirthara\Routing\RoutePattern;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\DataProvider;

use function preg_match;

final class RoutePatternTest extends TestCase
{
    /**
     * @return iterable<string, array{RoutePattern, string, bool}>
     */
    public static function values(): iterable
    {
        yield 'segment' => [RoutePattern::Segment, 'jörg doe.pdf', true];
        yield 'empty segment' => [RoutePattern::Segment, '', false];
        yield 'two segments' => [RoutePattern::Segment, 'a/b', false];
        yield 'integer' => [RoutePattern::Integer, '42', true];
        yield 'integer with letters' => [RoutePattern::Integer, '4a', false];
        yield 'negative integer' => [RoutePattern::Integer, '-4', false];
        yield 'uuid' => [RoutePattern::Uuid, '0b9f6ad4-5c4e-4e0f-9d2a-3f1c2b7a8e90', true];
        yield 'uppercase uuid' => [RoutePattern::Uuid, '0B9F6AD4-5C4E-4E0F-9D2A-3F1C2B7A8E90', true];
        yield 'uuid without dashes' => [RoutePattern::Uuid, '0b9f6ad45c4e4e0f9d2a3f1c2b7a8e90abcd', false];
        yield 'only dashes' => [RoutePattern::Uuid, '------------------------------------', false];
        yield 'slug' => [RoutePattern::Slug, 'hello-world-2026', true];
        yield 'slug with a trailing dash' => [RoutePattern::Slug, 'hello-', false];
        yield 'slug with a double dash' => [RoutePattern::Slug, 'hello--world', false];
        yield 'uppercase slug' => [RoutePattern::Slug, 'Hello', false];
    }

    #[Test]
    #[DataProvider('values')]
    public function it_matches_only_whole_valid_values(RoutePattern $pattern, string $value, bool $matches): void
    {
        self::assertSame($matches, preg_match('~^(?:' . $pattern->value . ')$~D', $value) === 1);
    }
}
