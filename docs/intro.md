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

$router->url('users.show', ['id' => 42]);         // '/users/42'
$router->urls()->action(CreateUser::class);        // '/users'
```

The router does not call the handler. A handler is any value you choose, such as a class name, a closure, or an array,
and `match()` hands it back with the parameters so your application can dispatch it.

| Class | Represents |
| --- | --- |
| `Router` | The registered routes, and the entry point for matching paths and generating URLs. |
| `Route` | One method, path, and handler, with an optional name and parameter constraints. |
| `RouteMatch` | The result of a successful match: the route and its decoded parameters. |
| `UrlGenerator` | Builds the path of a route from its name or its action. |
| `RouteCollection` | The routes in the order they were registered. |
| `HttpMethod` | The methods a route can be registered for. |
| `RoutePattern` | Ready-made constraints such as integers, UUIDs, ULIDs, slugs, and dates. |
| `TrailingSlash` | Whether a trailing slash in the path matters when matching. |

## Depend on the interfaces

The classes implement one interface for each part of the work, in the `Dirthara\Routing\Contract` namespace:

| Interface | Implemented by | Methods | Use it for |
| --- | --- | --- | --- |
| `Contract\RouteRegistrar` | `Router` | `add()`, `get()`, `head()`, `post()`, `put()`, `patch()`, `delete()`, `options()` | [Registering routes](defining-routes.md). |
| `Contract\RouteMatcher` | `Router` | `match()` | [Matching a request](matching-requests.md) to a route. |
| `Contract\UrlGenerator` | `UrlGenerator` | `name()`, `action()` | [Generating a path](generating-urls.md) by name or action. |
| `Contract\Route` | `Route` | `name()`, `where()`, `whereMany()`, `handler()`, `constraints()`, `parameters()`, `matches()`, `generatePath()`, and the `method`, `path`, and `name` properties | Naming and constraining a route, and reading it back. |

Type against the interface that matches what the code does, rather than against the class:

```php
use Dirthara\Routing\RoutePattern;
use Dirthara\Routing\Contract\RouteRegistrar;

final readonly class UserRoutes
{
    public function register(RouteRegistrar $routes): void
    {
        $routes->get('/users', ListUsers::class)->name('users.index');
        $routes->get('/users/{id}', ShowUser::class)->name('users.show')->where('id', RoutePattern::PositiveInteger);
    }
}
```

The registrar returns a `Contract\Route`, and `RouteMatch::$route` is typed as one too, so neither registering nor
matching reaches a concrete class. `$router->urls()` returns the router's `UrlGenerator`; bind `Contract\UrlGenerator`
to it in your container so link-building code can ask for the interface.

:::note
None of the interfaces extends another. Code that both registers and matches routes asks for both, or for `Router`.
`RouteMatch` and the enums are values, and `RouteCollection` is the router's storage; none of them has an interface.
:::

Read how to [define routes](defining-routes.md), how the router
[matches requests](matching-requests.md), how to [generate URLs](generating-urls.md), and which exceptions it throws in
[error handling](error-handling.md). See [installation](installation.md) for requirements and development setup.