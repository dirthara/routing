<?php

declare(strict_types=1);

namespace Dirthara\Routing;

enum RoutePattern: string
{
    case Integer = '\d+';
    case Uuid = '[0-9a-fA-F]{8}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{12}';
    case Slug = '[a-z0-9]+(?:-[a-z0-9]+)*';
}
