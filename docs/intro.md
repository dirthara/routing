---
id: intro
title: Dirthara Routing
sidebar_position: 1
description: Match HTTP methods and paths to handlers, and generate paths for named routes.
---

Dirthara Routing maps an HTTP method and a path to a handler, and builds the path back from a route's name and
parameters. It works on plain strings, so it has no runtime dependencies and needs no particular request class: pass it
the method and the path of whatever request you have.

```php
use Dirthara\Routing\Router;
use Dirthara\Routing\HttpMethod;
use Dirthara\Routing\RoutePattern;

$router = new Router();

$router->get('/users', ListUsers::class)->name('users.index');
$router->get('/users/{id}', ShowUser::class)->name('users.show')->where('id', RoutePattern::Integer);
$router->post('/users', CreateUser::class);

$match = $router->match(HttpMethod::Get, '/users/42');

$match->handler();    // ShowUser::class
$match->parameters;   // ['id' => '42']

$router->url('users.show', ['id' => 42]);   // '/users/42'
```

The router does not call the handler. A handler is any value you choose, such as a class name, a closure, or an array,
and `match()` hands it back with the parameters so your application can dispatch it.

| Class | Represents |
| --- | --- |
| `Router` | The registered routes, and the entry point for matching paths and generating URLs. |
| `Route` | One method, path, and handler, with an optional name and parameter constraints. |
| `RouteMatch` | The result of a successful match: the route and its decoded parameters. |
| `RouteCollection` | The routes in the order they were registered. |
| `HttpMethod` | The methods a route can be registered for. |
| `RoutePattern` | Ready-made constraints for integers, UUIDs, and slugs. |
| `TrailingSlash` | Whether a trailing slash in the path matters when matching. |

Read how to [define routes](defining-routes.md), how the router
[matches requests](matching-requests.md), how to [generate URLs](generating-urls.md), and which exceptions it throws in
[error handling](error-handling.md). See [installation](installation.md) for requirements and development setup.