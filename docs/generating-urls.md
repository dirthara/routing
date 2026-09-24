---
id: generating-urls
title: Generating URLs
sidebar_position: 5
description: Build the path of a named route from its parameters.
---

## Generating a path

`url()` builds the path of a [named route](defining-routes.md#naming-a-route) from its parameters.

```php
$router->get('/users/{id}/posts/{slug}', ShowPost::class)->name('posts.show');

$router->url('posts.show', ['id' => 7, 'slug' => 'hello-world']);   // '/users/7/posts/hello-world'
$router->url(Page::Home);                                           // '/'
```

The result is a path, not an absolute URL: add the scheme and host yourself when you need them. `Route::generatePath()`
does the same for a route you hold directly.

## Parameter values

A value is a string, an integer, a float, a boolean, or a `Stringable`. `true` becomes `1` and `false` becomes `0`.
Each value is percent-encoded, so it always stays within its own segment: `'annual report/2026.pdf'` becomes
`annual%20report%2F2026.pdf`, which matches the route and decodes back to the original value.

Every parameter of the route has to be given, and nothing else. There is no query string support; append one to the
result if you need it.

```php
$path = $router->url('users.index') . '?' . http_build_query(['page' => 2]);
```

## Validation

The generated path always matches the route it was generated for. `url()` checks each encoded value against the
parameter's constraint, or against the default of one or more characters when it has none.

| Situation | Exception |
| --- | --- |
| No route has the name. | `RouteNotFoundException` |
| More than one route has the name. | `InvalidRouteException` |
| A parameter of the route is not given. | `InvalidUrlParameterException` |
| A given parameter is not in the route's path. | `InvalidUrlParameterException` |
| A value does not match its constraint, or is empty. | `InvalidUrlParameterException` |

:::note
When a value does not match, the exception names the parameter and its pattern but not the value, because a path
parameter can hold a token that does not belong in a log.
:::