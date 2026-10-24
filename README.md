# Strukt Router

A typed HTTP router with parameter matching, a middleware pipeline, reflection
resolved actions, and form validation — built on the strukt4 packages.

Routes are declared against patterns such as `/user/{id:int}`, matched by a
compiled regex with typed parameter extraction, guarded by a middleware chain,
and dispatched by reflecting the action callable to inject the request, the
response, services, or a validated form.

```php
use Strukt\Contract\Http\RequestInterface;

$router = new Strukt\Router\Router();

$router->get('/', fn () => 'home');
$router->get('/user/{id:int}', fn (int $id) => "user[{$id}]");
$router->post('/login', fn (RequestInterface $request, Strukt\Contract\FormInterface $form) => $form->value('email'))
    ->form(LoginForm::class);

$response = $router->kernel()->handle($request);
$response->send();
```

## Install

```bash
composer require strukt/router
```

## Documentation

The full guide lives in [`docs/`](docs/README.md):

| Page | Covers |
| --- | --- |
| [Architecture](docs/architecture.md) | Layers, the dispatch pipeline, matching, exception mapping. |
| [Routing](docs/routing.md) | Verbs, patterns, typed parameters, groups, named routes. |
| [Middleware](docs/middleware.md) | The pipeline, global vs route, the built-in `sess`/`auth`/`authz`. |
| [Authentication](docs/authentication.md) | `verify()`, `permissions()`, log in / log out flow, 401/403. |
| [Forms](docs/forms.md) | Rule table, custom rules, custom messages, the 422 payload. |
| [Responses](docs/responses.md) | Action return values, redirects, downloads, error mapping. |
| [Dependency injection](docs/dependency-injection.md) | Parameter resolution, bindings, middleware constructors. |
| [HTTP layer](docs/http-layer.md) | `Strukt\Http` classes and the contracts they implement. |
| [Global helpers](docs/helpers.md) | `router()`, `request()`, `response()`, `route()`, `matcher()`. |
| [The route CLI](docs/cli.md) | Listing registered routes with `strukt-routes`. |
| [Testing](docs/testing.md) | Dispatching routes in Pest and asserting statuses. |
| [Troubleshooting](docs/troubleshooting.md) | The failure catalogue and debugging toolkit. |
| [API reference](docs/api-reference.md) | Signatures for every public class and method. |

## Examples

Runnable scripts covering routing, parameters, groups, middleware, forms,
authentication, responses, service injection, and the helper API live in
[`examples/`](examples/README.md):

```bash
php examples/01-basic-routing.php
```

## Routing

Use one verb method per route, or `any()` to accept every common method.
`get()`, `post()`, `put()`, `patch()`, `delete()`, `options()`, and `head()` all
share the same shape.

```php
$router->get('/list', fn () => 'list');
$router->post('/save', fn () => 'saved');
$router->any('/hook', fn () => 'any method');
```

### Groups

Wrap a callback with `group()` to share a path prefix.

```php
$router->group('/api/v1', function (Strukt\Router\Router $router) {
    $router->get('/users', fn () => 'users');
    $router->get('/users/{id:int}', fn (int $id) => "user[{$id}]");
});
```

### Named routes

`name()` assigns a route name for reverse generation with `path()`.

```php
$router->get('/user/{id:int}', fn (int $id) => "user[{$id}]")->name('user.show');

$router->path('user.show', ['id' => 7]); // '/user/7'
```

Route options — `name()`, `middleware()`, `permissions()`, `requiresAuth()`,
`form()`, and `defaults()` — mutate the registered route fluently and can be
stacked in any order.

## Parameters

A pattern is slash separated segments. A segment may be literal, a plain
placeholder, a typed placeholder, a wildcard, or an optional placeholder.

| Pattern | Matches | Type |
| --- | --- | --- |
| `{name}` | any segment | string |
| `{id:int}` | digits | int |
| `{price:float}` | decimal | float |
| `{on:bool}` | `true` / `false` | bool |
| `{word:alpha}` | letters | string |
| `{key:alphanum}` | letters, digits, `_`, `-` | string |
| `{slug}` | slug words | string |
| `{on:date}` | `YYYY-MM-DD` | string |
| `{id:uuid}` | UUID | string |
| `{rest:*}` | remaining segments | string |

```php
$router->get('/user/{id:int}', fn (int $id) => "user[{$id}]");
$router->get('/blog/{year:int}/{?month:int}', fn (int $year, ?int $month = null) => '');
$router->get('/files/{rest:*}', fn (string $rest) => $rest);
```

Matched values are cast, so `{id:int}` hands an `int` to the action. An unknown
type and a missing required placeholder both throw. Reverse generation through
`uri()` or `Router::path()` respects the same rules and route defaults.

## Middleware

Global middleware runs before every route; route middleware runs after that.
Definitions may be a registered name, a class, an object, or a callable.

```php
$router->middleware(TraceMiddleware::class);
$router->get('/admin', fn () => 'admin')->middleware('auth', 'authz');
```

Three names are registered out of the box:

| Name | Class | Purpose |
| --- | --- | --- |
| `sess` | `Middleware\Session` | Attach the session store to the request. |
| `auth` | `Middleware\Authentication` | Resolve and bind the current user. |
| `authz` | `Middleware\Authorization` | Enforce required permissions. |

Middleware resolves through constructor injection: a middleware class declares
the services it needs (`SessionInterface`, `AuthInterface`, a `$verify` or
`$permissions` callable) and the router supplies them from the per-request
service map.

## Authentication and permissions

`verify()` and `permissions()` configure how users and grants are resolved.
Routes opt in with `requiresAuth()` and `permissions([...])`.

```php
$router->session(fn (): SessionInterface => new Strukt\Http\Session\Native());
$router->verify(fn (SessionInterface $session): ?UserInterface => $session->get('email')
    ? new Strukt\User(email: $session->get('email'))
    : null);
$router->permissions(fn (?UserInterface $user): array => $user?->getEmail() === 'admin@example.com'
    ? ['admin']
    : []);

$router->get('/dashboard', fn (RequestInterface $request) => $request->user()?->getEmail() ?? 'guest')
    ->requiresAuth();

$router->get('/admin', fn () => 'admin')
    ->requiresAuth()
    ->permissions(['admin']);
```

A missing user on an authenticated route stops with a 401; a user without the
required permission receives a 403.

## Forms

Attach a `Strukt\Form\Form` class with `form()`. The router submits the request
values and validates before the action runs; a failing validation answers with
a 422 carrying the errors as JSON.

```php
$router->post('/login', fn (Strukt\Contract\FormInterface $form) => 'hi ' . $form->value('email'))
    ->form(LoginForm::class);
```

## Responses

An action may return a string (body), an array (JSON), a `ResponseInterface`,
or `null`. Use the `response()` helper for typed responses and redirects:

```php
$router->get('/home', fn () => response()->redirect('/home'));
$router->get('/data', fn () => response()->json(['ok' => true]));
$router->get('/file', fn () => download('/tmp/report.pdf', 'report.pdf'));
```

The kernel maps exceptions to HTTP errors: 404 for an unknown path, 405 with an
`Allow` header for a bad method, 422 for failed form validation, and 401 or 403
for the auth gates.

## Helpers

| Helper | Purpose |
| --- | --- |
| `router()` | The shared `Router` instance. |
| `request($input, $method, $uri, $headers)` | Build a `Request`. |
| `response($code, $headers)` | A fluent response builder. |
| `route($path, $method, $input)` | Dispatch and return the response. |
| `matcher($patterns)` | A standalone `UrlMatcher`. |
| `redirect($url, $status = 302)` | A redirect response. |
| `download($path, $filename)` | A file download. |

## HTTP layer

The package ships its own `Strukt\Http` stack implementing the
`strukt4/contract` interfaces: `Request` (with `createFromGlobals()`),
`Response`, `ResponseBuilder`, `Download`, and `UploadedFile`. There is no
dependency on a framework HTTP layer.

Session stores live under `Strukt\Http\Session`: `Native` persists values and
flashes in `$_SESSION`, and `Cache` persists them through a `strukt/cache`
driver — memcached, the filesystem, or memory — so several processes or servers
can share a session. Bind either with `$router->session()`:

```php
$router->session(fn (): SessionInterface => new Strukt\Http\Session\Native());
$router->session(fn (): SessionInterface => new Strukt\Http\Session\Cache(memc('sess')));
```

`Cache` needs the optional `strukt/cache` package. See
[HTTP layer](docs/http-layer.md#session-store) and
[installation](docs/installation.md#requirements).