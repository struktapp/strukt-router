# Quick start

## 1. Declare routes

```php
use Strukt\Router\Router;

$router = new Router();

$router->get('/', fn () => 'home');
$router->get('/user/{id:int}', fn (int $id) => "user[{$id}]");
$router->post('/ping', fn () => 'pong');
```

Each verb method returns the registered `Route`, so options stack fluently:

```php
$router->get('/dashboard', fn (RequestInterface $request) => $request->user()?->getEmail())
    ->requiresAuth()
    ->name('dashboard');
```

## 2. Dispatch a request

Build a `Request` and hand it to the kernel. `handle()` returns a response
without emitting anything:

```php
use Strukt\Http\Request;

$response = $router->kernel()->handle(new Request('GET', '/user/7'));

echo $response->status();   // 200
echo $response->body();     // user[7]
```

Or let the kernel emit it in one call:

```php
$router->kernel()->send(new Request('GET', '/user/7'));
```

## 3. Serve it from a web server

A front controller picks the live request up from the globals:

```php
<?php

require __DIR__.'/vendor/autoload.php';

use Strukt\Router\Router;

$router = new Router();

$router->get('/', fn () => 'it works!');
$router->get('/user/{id:int}', fn (int $id) => ['id' => $id]);

$router->kernel()->send();   // Request::createFromGlobals() under the hood
```

```bash
php -S localhost:8080 public/index.php
```

Because `handle()` with no arguments resolves the globals through
`Request::createFromGlobals()`, nothing else is needed. To override that — for
instance to inject a request from a framework — register a factory:

```php
$router->globals(fn (): RequestInterface => MyRequest::fromContainer());
```

## 4. Dispatch from a script or a test

There is no web server involved; construct the request by hand:

```php
use Strukt\Http\Request;

// POST with a body
$response = $router->kernel()->handle(new Request('POST', '/login', [], [
    'email' => 'ada@example.com',
    'password' => 'secret1',
]));

$response->status();   // 200, 401, 422 …
$response->body();     // payload
```

Requests are plain value objects, so dispatching is cheap and side-effect free
— see [Testing routes](testing.md).

## 5. See every feature in one script

The [examples](../examples/README.md) directory walks the whole surface in
order:

| Example | Shows |
| --- | --- |
| `01-basic-routing.php` | Verbs, dispatch, 404/405. |
| `02-typed-parameters.php` | Typed, optional, and wildcard placeholders. |
| `03-groups-and-named-routes.php` | Prefixes and `path()` reverse generation. |
| `04-middleware.php` | Global, route, and injected middleware. |
| `05-form-validation.php` | Forms and the 422 payload. |
| `06-authentication.php` | Login, 401, 403. |
| `07-responses.php` | JSON, redirects, downloads. |
| `08-service-injection.php` | Bindings and the `globals()` factory. |
| `09-web-front-controller.php` | The browser-facing front controller. |

## Next steps

- [Routing](routing.md) for the full pattern vocabulary.
- [Middleware](middleware.md) for guards around your actions.
- [Responses and errors](responses.md) for what an action may return.
