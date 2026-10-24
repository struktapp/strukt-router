# Strukt Router — Documentation

Strukt Router is a typed HTTP router for PHP 8.2 and later. Routes are
declared against patterns such as `/user/{id:int}`, matched by compiled regular
expressions with typed parameter extraction, guarded by a middleware pipeline,
and dispatched by reflecting the action callable to inject the request, the
response, services, or a validated form.

This documentation tree is the in-depth companion to the package
[README](../README.md) and the runnable [examples](../examples/README.md). It
is split into a topic guide, a reference, and an architecture deep dive.

## Topic guide

| Document | Covers |
| --- | --- |
| [Installation and requirements](installation.md) | Composer install, dependencies, autoloading, development install. |
| [Quick start](quickstart.md) | The fastest path from a route definition to a served response, with a front controller. |
| [Routing](routing.md) | Verb methods, patterns and typed placeholders, groups, named routes, reverse generation, route options. |
| [Middleware](middleware.md) | Global and route middleware, resolution by name/class/object/callable, constructor injection, the built-in `sess`, `auth`, and `authz` middleware. |
| [Authentication and permissions](authentication.md) | Session and auth gate factories, `verify()`, `permissions()`, `requiresAuth()`, the 401/403 gates. |
| [Forms and validation](forms.md) | Declaring `Strukt\Form` classes, the built-in rule table, custom rules, message overrides, the 422 payload. |
| [Responses and errors](responses.md) | Action return values, the `response()` builder, redirects, downloads, and the exception-to-status mapping. |
| [Dependency injection](dependency-injection.md) | The action resolution order, named bindings, per-request services, and the `globals()` factory. |
| [The HTTP layer](http-layer.md) | `Request`, `Response`, `ResponseBuilder`, `Download`, `UploadedFile`, and the contracts they satisfy. |
| [Testing routes](testing.md) | Dispatching requests in Pest, asserting statuses, bodies, headers, and middleware order. |
| [Troubleshooting](troubleshooting.md) | Every 500 message, the common 4xx surprises, and how to fix them. |

## Reference

| Document | Covers |
| --- | --- |
| [Global helpers](helpers.md) | The autoloaded helper functions: `router()`, `request()`, `response()`, `route()`, `redirect()`, `download()`, `matcher()`. |
| [The route CLI](cli.md) | The `routes` binary: usage, the routes file contract, the columns, and exit codes. |
| [API reference](api-reference.md) | Every public class, method, and interface, with signatures and contracts. |

## Architecture

[architecture.md](architecture.md) explains how the pieces fit together: the
dispatch pipeline from `handle()` to `wrap()`, the component responsibilities,
the exception mapping, the package boundaries, and the design decisions behind
the router.

## Where to look first

- New to the package? Read [Quick start](quickstart.md), then
  [Routing](routing.md).
- Wiring an application together? Read
  [Authentication and permissions](authentication.md) and
  [Dependency injection](dependency-injection.md).
- Debugging a 500? Open [Troubleshooting](troubleshooting.md).
