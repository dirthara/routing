---
id: error-handling
title: Error handling
sidebar_position: 6
description: The exception interface, the exception classes, and their diagnostic context.
---

## Catching exceptions

Every exception the package throws implements `Dirthara\Routing\Exception\RoutingException`, so one `catch` covers them
all. Each also extends the SPL exception that describes the failure: an invalid route definition or URL parameter throws
an `InvalidArgumentException`, and a path the router cannot route throws a `RuntimeException`.

```php
use Dirthara\Routing\Exception\RoutingException;
use Dirthara\Routing\Exception\RouteNotFoundException;

try {
    $match = $router->match($method, $path);
} catch (RouteNotFoundException $exception) {
    // no route matches the path
} catch (RoutingException $exception) {
    // anything else this package raised
}
```

| Exception | Extends | Thrown for |
| --- | --- | --- |
| `RouteNotFoundException` | `RuntimeException` | A path no route matches, and a name no route has. |
| `MethodNotAllowedException` | `RuntimeException` | A path that has routes, but none for the request method. |
| `InvalidRouteException` | `InvalidArgumentException` | Invalid paths, names, and constraints, and a name given to more than one route. |
| `InvalidUrlParameterException` | `InvalidArgumentException` | Missing, unknown, or non-matching parameters when generating a URL. |

All exception classes are `final`; catch them by class or by `RoutingException`.

## Context

Every exception carries a `context` array with the values that describe the failure, such as the `method` and `path`
that did not match, or the `name`, `path`, and `parameter` of a URL that could not be generated. Add your own with
`addContext()`, which merges into what is there and returns the exception:

```php
try {
    $match = $router->match($method, $path);
} catch (RoutingException $exception) {
    throw $exception->addContext(['host' => $request->getUri()->getHost()]);
}
```

Control characters in a path, name, or pattern are escaped in the message, so a request path cannot forge extra lines
in a log. The context keeps the original values.

The package never logs, and never includes a URL parameter's value in a message or in context.