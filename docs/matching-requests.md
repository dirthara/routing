---
id: matching-requests
title: Matching requests
sidebar_position: 4
description: How the router matches a method and a path, and how it handles HEAD and trailing slashes.
---

## Matching

`match()` takes the request method and path and returns a `RouteMatch` for the route that handles them.

```php
use Dirthara\Routing\HttpMethod;

$match = $router->match($request->getMethod(), $request->getUri()->getPath());

$match->route;        // the Route that matched
$match->parameters;   // ['id' => '42'], decoded
$match->handler();    // the route's handler
```

The method is an `HttpMethod` case or a string. Strings are compared exactly, as HTTP method names are case-sensitive:
`'GET'` matches a GET route and `'get'` does not. An empty path is treated as `/`.

Routes are tried in the order they were registered, and the first route whose method and path match wins. Register a
specific route before a general one that would also match it.

Parameter values are percent-decoded, so `/files/annual%20report.pdf` gives `['name' => 'annual report.pdf']`. A `+`
stays a `+`; it only means a space in a query string, not in a path.

## When nothing matches

| Situation | Exception |
| --- | --- |
| No route matches the path, for any method. | `RouteNotFoundException` |
| Routes match the path, but none for this method. | `MethodNotAllowedException` |

`MethodNotAllowedException::$allowedMethods` lists the methods the path does have routes for, in registration order, so
a 405 response can send them in its `Allow` header.

```php
use Dirthara\Routing\Exception\RouteNotFoundException;
use Dirthara\Routing\Exception\MethodNotAllowedException;

try {
    $match = $router->match($method, $path);
} catch (RouteNotFoundException) {
    return $responseFactory->createResponse(404);
} catch (MethodNotAllowedException $exception) {
    return $responseFactory->createResponse(405)->withHeader('Allow', implode(', ', $exception->allowedMethods));
}
```

A method the `HttpMethod` enum does not name, such as `PROPFIND`, never matches a route; on a path that has routes for
other methods it gives a `MethodNotAllowedException`.

## HEAD requests

A HEAD request is answered by a GET route for the same path when there is no HEAD route for it, because a HEAD response
is a GET response without the body. An explicit HEAD route always takes precedence, wherever it is registered. For the
same reason, `allowedMethods` includes `HEAD` whenever it includes `GET`.

The router does not strip the body; that is up to the code that sends the response.

## Trailing slashes

The router's `trailingSlash` option decides whether `/users` and `/users/` are the same path.

| Option | Type | Default | Meaning |
| --- | --- | --- | --- |
| `trailingSlash` | `TrailingSlash` | `TrailingSlash::Ignore` | `Ignore` matches a route with or without one trailing slash, however the route was defined. `Strict` requires the path to match the route exactly. |

```php
use Dirthara\Routing\Router;
use Dirthara\Routing\TrailingSlash;

$router = new Router(trailingSlash: TrailingSlash::Strict);
```

`Ignore` is the default because a trailing slash is rarely meaningful to a user and is easy to add by accident. With
`Strict`, `/users/` gives a `RouteNotFoundException` when only `/users` is registered. The root path `/` matches only
`/` or an empty path under either option.