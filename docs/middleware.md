# Middleware

Middleware wraps every dispatched route: it sees the request and the response,
does its work, and hands control to the next link through `$next`.

- [Where middleware runs](#where-middleware-runs)
- [Declaring middleware](#declaring-middleware)
- [Resolving a definition](#resolving-a-definition)
- [Constructor injection](#constructor-injection)
- [The built-in middleware](#the-built-in-middleware)
- [Writing your own](#writing-your-own)
- [Ordering and short circuits](#ordering-and-short-circuits)

## Where middleware runs

Middleware belongs to a **matched route**. The pipeline in
`Kernel::dispatch()` is:

```
global middleware   (Router::middleware(), every route)
route middleware    (Route::middleware(), one route)
permission gate     (Route::permissions(), appended last)
action              (Invoker::invoke())
```

Two details worth knowing:

- **404 and 405 never reach middleware.** The path is matched before the chain
  is built, so unmatched requests are answered by the kernel's error mapping
  with no middleware involved.
- **The request and response are carried by reference.** Middleware and the
  action receive the same objects, so a header set before `$next()` is visible
  to the action, and a value set by the action is visible after `$next()`
  returns.

## Declaring middleware

Global middleware applies to every matched route; route middleware applies to
one. Both accept names, class strings, objects, callables, and arrays of
those:

```php
use Strukt\Router\Router;

$router = new Router();

// global — every route
$router->middleware(TimerMiddleware::class);
$router->middleware(new TimerMiddleware(), CallableMiddleware::class);

// route — this route only, appended after the globals
$router->get('/admin', fn () => 'admin')
    ->middleware('auth', 'authz', AdminLogger::class);
```

`Route::middleware()` **appends**; repeated calls stack:

```php
$route = $router->get('/x', fn () => 'x');
$route->middleware('sess');
$route->middleware('auth');   // ['sess', 'auth']
```

## Resolving a definition

`Router::makeMiddleware()` turns a definition into a `MiddlewareInterface`:

| Definition | How it resolves |
| --- | --- |
| Registered name (`'sess'`) | Looked up in the named table, then resolved as that entry. |
| Class string | Instantiated with constructor injection from the per-request services. |
| Object implementing `MiddlewareInterface` | Used as-is. |
| Any other callable | Wrapped in an anonymous `MiddlewareInterface` adapter. |

Names are registered out of the box (`sess`, `auth`, `authz`), and you add
your own with `Router::named()`:

```php
$router->named('signed', SignerMiddleware::class);

$router->get('/hello', fn () => 'hi')->middleware('signed');
```

A definition that resolves to neither a class, an object, nor a callable — or
to a class that does not implement `MiddlewareInterface` — throws
`ResolutionException`, which the kernel reports as a 500.

## Constructor injection

Middleware constructors are populated from the per-request service map, in
this order for each parameter:

1. a **binding of the same parameter name** (`$router->bind('api', …)`)
2. the `verify` callable, when the parameter is named `$verify`
3. the `permissions` callable, when the parameter is named `$permissions`
4. typed `SessionInterface`
5. typed `AuthInterface`
6. a **binding of the same type** (`$router->bind(SomeInterface::class, …)`)
7. a declared default value
8. a nullable parameter
9. otherwise `ResolutionException`

That is why the built-in middleware is so small — it just declares what it
needs:

```php
final class SignerMiddleware implements MiddlewareInterface
{
    public function __construct(private SessionInterface $session) {}

    public function process(RequestInterface $request, ResponseInterface $response, callable $next): ResponseInterface
    {
        return $next($request, $response->setHeader('X-Tenant', $this->session->get('tenant', 'root')));
    }
}
```

## The built-in middleware

Three names are registered by default:

| Name | Class | Constructor | Behaviour |
| --- | --- | --- | --- |
| `sess` | `Middleware\Session` | `(SessionInterface $session)` | Attaches the session store to the concrete `Request` so later middleware and actions can read it via `$request->session()`. |
| `auth` | `Middleware\Authentication` | `(AuthInterface $auth, ?callable $verify = null, ?SessionInterface $session = null)` | Resolves the current user — through the `verify` resolver when one is configured and a session is available, otherwise `$auth->user()` — and binds it to the request. |
| `authz` | `Middleware\Authorization` | `(callable $resolver, array $require = [])` | Calls the permission resolver with the bound user and intersects the result with the required names. No match → 401 for a guest, 403 for a signed-in user. |

`sess` and `auth` are conveniences for *reading*; the enforcement you usually
want comes from the route options `requiresAuth()` and `permissions()`, which
the kernel wires itself — see
[Authentication and permissions](authentication.md).

## Writing your own

Implement `MiddlewareInterface::process()` and **always return the response**
you pass on, so a middleware may short-circuit:

```php
use Strukt\Contract\Http\RequestInterface;
use Strukt\Contract\Http\ResponseInterface;
use Strukt\Contract\MiddlewareInterface;

final class TimingMiddleware implements MiddlewareInterface
{
    public function process(RequestInterface $request, ResponseInterface $response, callable $next): ResponseInterface
    {
        $start = microtime(true);
        $result = $next($request, $response);

        return $result->setHeader('X-Took-Ms', sprintf('%.3f', (microtime(true) - $start) * 1000));
    }
}
```

A short-circuit simply does not call `$next()`:

```php
final class MaintenanceMiddleware implements MiddlewareInterface
{
    public function process(RequestInterface $request, ResponseInterface $response, callable $next): ResponseInterface
    {
        if (!$this->open()) {
            return $response->setStatus(503)->setBody('Down for maintenance');
        }

        return $next($request, $response);
    }
}
```

Closures work too — the signature is the same three arguments:

```php
$router->get('/armed', fn () => 'ok')->middleware(
    fn (RequestInterface $request, ResponseInterface $response, callable $next) => $next(
        $request,
        $response->setHeader('X-Arm', 'yes'),
    ),
);
```

## Ordering and short circuits

Within one dispatch, steps run in this order:

```
Router::middleware() entries   in the order they were registered
Route::middleware() entries    in the order they were appended
Authorization gate             appended by the kernel when permissions() is set
```

Composition is recursive: `Kernel::run()` pops the first step, wraps the rest
as `$next`, and calls `process()`. So for `[A, B]` the call nesting is
`A → B → action`, and anything `A` does *after* `$next()` runs after `B` and
the action have finished.

A short-circuiting middleware stops everything after it: neither the
remaining middleware nor the action runs, and whatever response it returns is
the one the kernel wraps and sends.

## Next steps

- [Authentication and permissions](authentication.md) for the guards.
- [Dependency injection](dependency-injection.md) for the full resolution rules.
- [Testing routes](testing.md) for asserting middleware order.
