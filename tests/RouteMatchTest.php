<?php

declare(strict_types=1);

namespace Dirthara\Routing\Tests;

use Dirthara\Routing\Route;
use PHPUnit\Framework\TestCase;
use Dirthara\Routing\HttpMethod;
use Dirthara\Routing\RouteMatch;
use PHPUnit\Framework\Attributes\Test;

final class RouteMatchTest extends TestCase
{
    #[Test]
    public function it_exposes_the_route_its_parameters_and_its_handler(): void
    {
        $route = new Route(HttpMethod::Get, '/users/{id}', 'users.show');
        $match = new RouteMatch($route, ['id' => '1']);

        self::assertSame($route, $match->route);
        self::assertSame(['id' => '1'], $match->parameters);
        self::assertSame('users.show', $match->handler());
    }
}
