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
        yield 'positive integer' => [RoutePattern::PositiveInteger, '42', true];
        yield 'positive integer of one digit' => [RoutePattern::PositiveInteger, '7', true];
        yield 'zero' => [RoutePattern::PositiveInteger, '0', false];
        yield 'positive integer with a leading zero' => [RoutePattern::PositiveInteger, '007', false];
        yield 'alpha' => [RoutePattern::Alpha, 'FAQ', true];
        yield 'alpha with a digit' => [RoutePattern::Alpha, 'faq2', false];
        yield 'alpha with a non-ASCII letter' => [RoutePattern::Alpha, 'jörg', false];
        yield 'alphanumeric' => [RoutePattern::Alphanumeric, 'abc123XYZ', true];
        yield 'alphanumeric with a dash' => [RoutePattern::Alphanumeric, 'abc-123', false];
        yield 'hex' => [RoutePattern::Hex, 'DeadBeef0123', true];
        yield 'hex with a non-hex letter' => [RoutePattern::Hex, 'deadbeeg', false];
        yield 'ulid' => [RoutePattern::Ulid, '01ARZ3NDEKTSV4RRFFQ69G5FAV', true];
        yield 'lowercase ulid' => [RoutePattern::Ulid, '01arz3ndektsv4rrffq69g5fav', true];
        yield 'largest ulid' => [RoutePattern::Ulid, '7ZZZZZZZZZZZZZZZZZZZZZZZZZ', true];
        yield 'ulid beyond the timestamp' => [RoutePattern::Ulid, '8ZZZZZZZZZZZZZZZZZZZZZZZZZ', false];
        yield 'ulid with an excluded letter' => [RoutePattern::Ulid, '01ARZ3NDEKTSV4RRFFQ69G5FAI', false];
        yield 'ulid of 25 characters' => [RoutePattern::Ulid, '01ARZ3NDEKTSV4RRFFQ69G5FA', false];
        yield 'date' => [RoutePattern::Date, '2026-09-24', true];
        yield 'last day of a month' => [RoutePattern::Date, '2026-12-31', true];
        yield 'impossible day in a possible range' => [RoutePattern::Date, '2026-02-31', true];
        yield 'thirteenth month' => [RoutePattern::Date, '2026-13-01', false];
        yield 'day zero' => [RoutePattern::Date, '2026-01-00', false];
        yield 'thirty-second day' => [RoutePattern::Date, '2026-01-32', false];
        yield 'date without padding' => [RoutePattern::Date, '2026-9-24', false];
        yield 'year' => [RoutePattern::Year, '2026', true];
        yield 'two-digit year' => [RoutePattern::Year, '26', false];
        yield 'five-digit year' => [RoutePattern::Year, '20260', false];
        yield 'language' => [RoutePattern::Locale, 'en', true];
        yield 'language and region' => [RoutePattern::Locale, 'en-US', true];
        yield 'lowercase region' => [RoutePattern::Locale, 'en-us', false];
        yield 'underscore locale' => [RoutePattern::Locale, 'en_US', false];
        yield 'script subtag' => [RoutePattern::Locale, 'zh-Hant-TW', false];
    }

    #[Test]
    #[DataProvider('values')]
    public function it_matches_only_whole_valid_values(RoutePattern $pattern, string $value, bool $matches): void
    {
        self::assertSame($matches, preg_match('~^(?:' . $pattern->value . ')$~D', $value) === 1);
    }
}
