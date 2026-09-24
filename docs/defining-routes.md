---
id: defining-routes
title: Defining routes
sidebar_position: 3
description: Register routes, name them, and constrain their parameters.
---

## Registering a route

Every method has a shortcut on the router. Each returns the new `Route`, so you can name and constrain it in the same
statement.

```php
use Dirthara\Routing\Router;
use Dirthara\Routing\HttpMethod;

$router = new Router();

$router->get('/users', ListUsers::class);
$router->head('/users', CountUsers::class);
$router->post('/users', CreateUser::class);
$router->put('/users/{id}', ReplaceUser::class);
$router->patch('/users/{id}', UpdateUser::class);
$router->delete('/users/{id}', DeleteUser::class);
$router->options('/users', DescribeUsers::class);

$router->add(HttpMethod::Get, '/about', ShowAbout::class);
```

A handler can be any value. The router stores it and returns it from `Route::handler()` and `RouteMatch::handler()`,
and never calls it.

## Paths and parameters

A path starts with `/`. A part of the path in braces, such as `{id}`, is a parameter: it matches a piece of the request
path and is returned by name when the route matches.

```php
$router->get('/users/{user}/posts/{post}', ShowPost::class);
$router->get('/files/{name}.{format}', DownloadFile::class);
```

A parameter name starts with a letter or an underscore, contains only letters, digits, and underscores, is at most 32
characters long, and appears once per path. A path that breaks these rules, has a `{` or `}` outside a parameter, or
does not start with `/` throws an `InvalidRouteException` when it is registered.

:::note
Without a constraint, a parameter matches one path segment: one or more characters other than `/`, the pattern of
`RoutePattern::Segment`. In `/files/{name}.{format}`, that means `{name}` can also swallow dots; constrain it if the
split matters.
:::

## Optional parameters

A parameter with a `?` after its name, such as `{page?}`, is optional: the route matches with or without it.

```php
$router->get('/posts/{page?}', ListPosts::class)->where('page', RoutePattern::Integer);
```

That route matches `/posts` and `/posts/2`. When the segment is missing, the parameter is left out of
`RouteMatch::$parameters` rather than set to a default, so check for it:

```php
$page = (int) ($match->parameters['page'] ?? 1);
```

An optional parameter has to be the whole last segment of the path, which makes it the only optional parameter the
path can have. `/{lang?}/about`, `/posts/{page?}.json`, `/posts/page{page?}`, and `/posts/{page?}/` throw an
`InvalidRouteException`, because with the segment missing it would be ambiguous what the rest of the path should look
like. Register a second route for those cases instead.

`/{page?}` is allowed and matches `/` as well as `/2`. A constraint on an optional parameter applies when the segment is
there; `/posts/last` does not match the route above.

## Constraining parameters

`where()` restricts what a parameter matches with a regular expression. A route whose parameter does not match is
skipped, and the router tries the next route.

```php
use Dirthara\Routing\RoutePattern;

$router->get('/users/{id}', ShowUser::class)->where('id', RoutePattern::Integer);
$router->get('/users/{name}', ShowUserByName::class);

$router->get('/reports/{year}/{format}', ShowReport::class)->whereMany([
    'year' => '\d{4}',
    'format' => 'csv|json',
]);
```

With those routes, `/users/42` matches `ShowUser` and `/users/jane` falls through to `ShowUserByName`.

The pattern always has to match the whole parameter, so write it without `^`, `$`, or delimiters; `csv|json` matches
`csv` or `json` and nothing longer. Setting a constraint again replaces the earlier one, so
`->where('id', RoutePattern::Segment)` puts a parameter back to the default. `constraints()` returns the constraints set
so far, keyed by parameter name, including one set back to the default.

| `RoutePattern` case | Pattern | Matches |
| --- | --- | --- |
| `Segment` | `[^/]+` | One or more characters other than `/`: a whole path segment. This is the default. |
| `Integer` | `\d+` | One or more digits, such as `42`. No sign. |
| `Uuid` | `[0-9a-fA-F]{8}-…-[0-9a-fA-F]{12}` | A UUID in its 8-4-4-4-12 form, in either case. |
| `Slug` | `[a-z0-9]+(?:-[a-z0-9]+)*` | Lowercase words joined by single dashes, such as `hello-world`. |

A constraint for a parameter the path does not have, or a pattern that is not a valid regular expression, throws an
`InvalidRouteException`. Patterns are delimited with `~` internally, so a pattern that contains `~` is rejected as
invalid; write `\~` instead.

:::caution
Parameters are matched against the path as it arrives, still percent-encoded, and decoded afterwards. A constraint
sees `j%C3%B6rg`, not `jörg`, so write constraints for the encoded form.
:::

## Naming a route

`name()` gives a route a name, as a string or as an enum case, so you can [generate its URL](generating-urls.md) later.

```php
enum Page
{
    case Home;
    case Contact;
}

$router->get('/', ShowHome::class)->name(Page::Home);
$router->get('/users/{id}', ShowUser::class)->name('users.show');
```

An enum case and a string are different names, even when the string reads like the case: `'Home'` does not find the
route named `Page::Home`. An empty string throws an `InvalidRouteException`. Giving two routes the same name is not
rejected when the name is set, because a route can be named after it has been added; it is rejected when the name is
used to generate a URL.