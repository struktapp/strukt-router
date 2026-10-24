# Strukt Router — Examples

Runnable scripts demonstrating the routing, matching, middleware, auth, forms,
response, and service-injection features of `strukt/router`. Each file loads the
package autoloader and prints its results to stdout.

## Run from the package root

```bash
# one example
php examples/01-basic-routing.php

# every example, in order
for f in examples/[0-9][0-9]-*.php; do
  echo "== $f =="
  php "$f"
done
```

## Run as a web server

`examples/09-web-front-controller.php` also works as a front controller:

```bash
php -S localhost:8080 examples/09-web-front-controller.php
```

Then open `http://localhost:8080/`, `/user/7`, `/search?q=strukt`, or `/old`.

## Contents

| File | Shows |
| --- | --- |
| `01-basic-routing.php` | Verb methods, `any()`, dispatch via the kernel, 404/405 handling. |
| `02-typed-parameters.php` | Typed placeholders (`int`, `float`, `bool`, `slug`, `date`, `uuid`, wildcard, optional). |
| `03-groups-and-named-routes.php` | Groups with prefixes, `name()` + `path()` reverse generation, `defaults()`. |
| `04-middleware.php` | Global middleware objects, route-scoped closures, custom named middleware with constructor injection. |
| `05-form-validation.php` | `Strukt\Form` subclasses, rule definitions, 422 error payloads. |
| `06-authentication.php` | Session-backed auth, `verify()`/`permissions()`, `requiresAuth()`, 401/403 gates. |
| `07-responses.php` | String/array/`ResponseInterface` results, `response()` builder, redirects, downloads. |
| `08-service-injection.php` | Named bindings, injected request/session/auth, `globals()` factory. |
| `09-web-front-controller.php` | The kernel against `createFromGlobals()` and `send()`; also CLI-simulatable. |
| `10-url-matcher.php` | The low-level `UrlMatcher`: `compile()`, `params()`, `uri()`, `which()`, `lastParams()`. |
| `11-helpers.php` | The global `router()`, `request()`, `response()`, `route()`, `redirect()`, `download()`, `matcher()` helpers. |