<?php

declare(strict_types=1);

namespace Dirthara\Routing;

enum TrailingSlash
{
    case Ignore;
    case Strict;
}
