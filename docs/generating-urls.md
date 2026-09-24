---
id: generating-urls
title: Generating URLs
sidebar_position: 5
description: Build the path of a route from its name or its action and its parameters.
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

## The URL generator

`UrlGenerator` builds paths by a route's name or by its action. `$router->urls()` returns the router's generator, which
sees every route the router has, including routes added after you took it. Pass it to the code that builds links, so
that code does not need the whole router.

```php
use Dirthara\Routing\UrlGenerator;

$urls = $router->urls();

$urls->name('posts.show', ['id' => 7, 'slug' => 'hello-world']);   // '/users/7/posts/hello-world'
```

`name()` does exactly what `$router->url()` does; `url()` is a shortcut for it.

## By action

`action()` finds the route whose handler is the given action, so a route does not need a name to be linked to.

```php
$router->get('/users', ListUsers::class);
$router->get('/users/{id}', [UserController::class, 'show']);

$urls->action(ListUsers::class);                                // '/users'
$urls->action([UserController::class, 'show'], ['id' => 7]);    // '/users/7'
```

The action is compared with the handler using `===`: a class name matches the same string, and a controller and method
match an array with the same two strings in the same order. An object matches only the same instance.

:::caution
Every closure is a separate object, so a route with a closure handler can only be found by action with the very same
closure. Name those routes instead.
:::

One action often handles more than one route, such as an edit form on GET and its submission on PUT. Pass a method to
choose between them:

```php
use Dirthara\Routing\HttpMethod;

$router->get('/users/{id}/edit', [UserController::class, 'edit']);
$router->put('/users/{id}', [UserController::class, 'edit']);

$urls->action([UserController::class, 'edit'], ['id' => 7], HttpMethod::Get);   // '/users/7/edit'
$urls->action([UserController::class, 'edit'], ['id' => 7], HttpMethod::Put);   // '/users/7'
```

The method has to match the route's method exactly; `HttpMethod::Head` does not find a GET route. When the action
still belongs to more than one route, with or without a method, `action()` throws an `InvalidRouteException` that lists
them, rather than picking one: the right choice depends on which link you mean, and only the caller knows that.

## Parameter values

A value is a string, an integer, a float, a boolean, or a `Stringable`. `true` becomes `1` and `false` becomes `0`.
Each value is percent-encoded, so it always stays within its own segment: `'annual report/2026.pdf'` becomes
`annual%20report%2F2026.pdf`, which matches the route and decodes back to the original value.

Every parameter of the route has to be given, except an [optional parameter](defining-routes.md#optional-parameters),
and nothing else. Leaving out the optional parameter leaves out its segment and the slash before it:

```php
$router->get('/posts/{page?}', ListPosts::class)->name('posts.index');

$router->url('posts.index');                  // '/posts'
$router->url('posts.index', ['page' => 2]);   // '/posts/2'
```

There is no query string support; append one to the result if you need it.

```php
$path = $router->url('users.index') . '?' . http_build_query(['page' => 2]);
```

## Validation

The generated path always matches the route it was generated for. Each encoded value is checked against the
parameter's constraint, or against the default of one or more characters when it has none.

| Situation | Exception |
| --- | --- |
| No route has the name, or the action for the given method. | `RouteNotFoundException` |
| More than one route has the name, or the action for the given method. | `InvalidRouteException` |
| A required parameter of the route is not given. | `InvalidUrlParameterException` |
| A given parameter is not in the route's path. | `InvalidUrlParameterException` |
| A value does not match its constraint, or is empty. | `InvalidUrlParameterException` |

:::note
When a value does not match, the exception names the parameter and its pattern but not the value, because a path
parameter can hold a token that does not belong in a log.
:::