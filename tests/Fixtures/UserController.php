<?php

declare(strict_types=1);

namespace Dirthara\Routing\Tests\Fixtures;

final readonly class UserController
{
    public function index(): string
    {
        return 'index';
    }

    public function show(): string
    {
        return 'show';
    }
}
