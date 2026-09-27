# Security Policy

## Supported versions

| Branch | Releases | Status |
| --- | --- | --- |
| `0.1` | 0.1.x | Active |

While the package is pre-1.0, only the latest release line receives fixes.

## Reporting a vulnerability

Report vulnerabilities privately using GitHub's
[Report a vulnerability](https://github.com/dirthara/routing/security/advisories/new)
form. Do not disclose vulnerabilities in public issues or pull requests.

Include the affected version or commit, PHP version, a minimal reproduction,
and the impact and conditions needed to trigger the issue. Maintainers will
acknowledge and assess the report. Confirmed fixes are published with an
advisory crediting the reporter unless they prefer otherwise.

## Scope

The router treats the request method and path passed to `match()` as untrusted.
In scope are flaws in how the package handles them, for example:

- a path that matches a route it should not, or that escapes a parameter's
  constraint or its own segment;
- a parameter value that is decoded differently from how it was matched;
- a generated path that does not match its own route, or in which a parameter
  value breaks out of its segment;
- a built-in `RoutePattern` that accepts input its documentation excludes, or
  that backtracks catastrophically on crafted input.

Route definitions are trusted. Paths, handlers, names, and custom constraint
patterns come from the application, so a slow or permissive pattern the
application supplies is not a vulnerability in this package. Neither is what a
handler does with a matched parameter: the router checks a value against its
constraint and decodes it, but validating, authorizing, and escaping it for
its eventual use are the application's responsibility.

Bugs in PHP or third-party dependencies should also be reported upstream.
