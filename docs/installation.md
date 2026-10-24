# Installation and requirements

## Requirements

Strukt Router targets PHP **8.2 and later**.

There are no required PHP extensions: routing, matching, middleware, forms, and
dispatch run on stock PHP under any SAPI — FPM, Apache, the CLI server, or a
plain terminal process.

The optional cache-backed session store
(`Strukt\Http\Session\Cache`, see [`http-layer.md`](http-layer.md)) needs the
`strukt/cache` package, which in turn needs `ext-zip`; its memcached driver
needs `ext-memcached` and a running server. Nothing else in the package touches
the cache.

## Composer

```bash
composer require strukt/router
```

The package pulls in four strukt4 dependencies:

| Package | Provides |
| --- | --- |
| `strukt/contract` | The interfaces your code type-hints (`RequestInterface`, `ResponseInterface`, `MiddlewareInterface`, `SessionInterface`, `AuthInterface`, `FormInterface`, `UserInterface`) plus the reference implementations `Strukt\Session`, `Strukt\Auth`, and `Strukt\User`. |
| `strukt/form` | `Strukt\Form\Form`, the rule registry, and the validator used by route forms. |
| `strukt/commons` | The helper registry (`helper_add()`, `reg()`) that backs the global helpers. |
| `strukt/base` | Shared base utilities. |

Because the router ships its own HTTP layer, you do **not** need a framework,
PSR-7, or any web-server integration package.

`strukt/cache` is a dev dependency, so it is used by the test suite but is not
pulled into a production install. Add it explicitly when you want the
cache-backed session store:

```bash
composer require strukt/cache
```

## Autoloading

The package registers one PSR-4 mapping and one file mapping:

- `Strukt\` → `src/Strukt/` (classes)
- `src/helpers.php` (global helper functions)

Once Composer's autoloader is in play, `router()`, `request()`,
`response()`, `route()`, `redirect()`, `download()`, and `matcher()` are
available in every file. Each helper checks `function_exists()` first, so an
application may define any of those names itself and win.

## Running the examples

The repository ships runnable scripts covering every feature:

```bash
php examples/01-basic-routing.php
```

See [`examples/README.md`](../examples/README.md) for the full list, or run the
front controller under the built-in PHP server:

```bash
php -S localhost:8080 examples/09-web-front-controller.php
```

## Development install

If you cloned the source, install dev dependencies and run the suite:

```bash
composer update
composer test      # Pest suite
composer lint      # php -l on src, tests, and examples
composer validate --no-check-publish
```

The suite covers verbs, matching and typed parameters, reverse generation,
groups, middleware ordering, injection, forms and 422 payloads, the auth and
permission gates, response building, and the HTTP layer.

## Next steps

- [Quick start](quickstart.md) for a served response in a few lines.
- [Routing](routing.md) for patterns, groups, and named routes.
