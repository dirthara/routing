<?php

namespace Dirthara\Routing;

enum RoutePattern: string
{
    case Integer = '\d+';
    case Uuid = '[0-9a-fA-F-]{36}';
    case Slug = '[a-z0-9]+(?:-[a-z0-9]+)*';
}