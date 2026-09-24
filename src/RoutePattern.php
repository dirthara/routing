<?php

declare(strict_types=1);

namespace Dirthara\Routing;

enum RoutePattern: string
{
    case Segment = '[^/]+';
    case Integer = '\d+';
    case PositiveInteger = '[1-9]\d*';
    case Alpha = '[A-Za-z]+';
    case Alphanumeric = '[A-Za-z0-9]+';
    case Hex = '[0-9a-fA-F]+';
    case Uuid = '[0-9a-fA-F]{8}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{12}';
    case Ulid = '[0-7][0-9A-HJKMNP-TV-Za-hjkmnp-tv-z]{25}';
    case Slug = '[a-z0-9]+(?:-[a-z0-9]+)*';
    case Date = '\d{4}-(?:0[1-9]|1[0-2])-(?:0[1-9]|[12]\d|3[01])';
    case Year = '\d{4}';
    case Locale = '[a-z]{2}(?:-[A-Z]{2})?';
}
