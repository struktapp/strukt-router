# Dependency injection

The router has no container. It reflects each action (and each middleware
constructor) and resolves parameters one by one from the matched route, the
current request, the per-request services, and your bindings.

- [The services a dispatch provides](#the-services-a-dispatch-provides)
- [Action argument resolution](#action-argument-resolution)
- [Injectable types](#injectable-types)
- [Named bindings](#named-bindings)
- [Middleware constructor resolution](#middleware-constructor-resolution)
- [The globals factory](#the-globals-factory)
- [When resolution fails](#when-resolution-fails)

## The services a dispatch provides

`Router::services()` builds one map per dispatch:

```php
[
    'session'     => SessionInterface,        // from the session factory
    'auth'        => AuthInterface,           // the auth gate for that session
    'verify'      => ?callable,               // Router::verify() or null
    'permissions' => ?callable,               // Router::permissions() or a default []
    'bindings'    => array<string, mixed>,    // Router::bind() entries
]
```

Everything handed to middleware and actions comes from this map or from the
route being dispatched. Nothing persists between dispatches except what you
keep in your own closures (see
[Session persistence](authentication.md#session-persistence-across-dispatches)).

## Action argument resolution

`Invoker::invoke()` reflects the action and, for each parameter, tries — in
order:

| # | Test | Example parameter | Resolves to |
| --- | --- | --- | --- |
| 1 | matched route parameter of the same **name** | `fn (int $id)` on `/user/{id:int}` | the typed, cast value |
| 2 | type `RequestInterface` | `fn (RequestInterface $request)` | the request being dispatched |
| 3 | type `ResponseInterface` | `fn (ResponseInterface $response)` | the response under construction |
| 4 | type `SessionInterface` | `fn (SessionInterface $session)` | `$services['session']` |
| 5 | type `AuthInterface` | `fn (AuthInterface $auth)` | `$services['auth']` |
| 6 | type `FormInterface` | `fn (FormInterface $form)` | the route's form, submitted and validated |
| 7 | a **binding of the same name** | `fn ($greeting)` after `bind('greeting', …)` | the bound value or the factory result |
| 8 | declared default | `fn (int $page = 1)` | the default |
| 9 | nullable type | `fn (?Config $config)` | `null` |
| 10 | otherwise | — | `ResolutionException` → 500 |

The order matters: a route parameter always wins, and a binding is consulted
**by parameter name**, not by type.

```php
$router->get('/user/{id:int}', fn (int $id) => "user {$id}");       // rule 1
$router->get('/who', fn (RequestInterface $r) => $r->path());       // rule 2
$router->session(fn () => new Session(['region' => 'eu']));
$router->get('/region', fn (SessionInterface $s) => $s->get('region'));  // rule 4
$router->bind('greeting', fn () => 'hello');
$router->get('/hi', fn (string $greeting) => "say {$greeting}");    // rule 7
```

### Note on types

Only the five interfaces in rules 2–6 are recognised **by type** in an
action. A parameter typed `SomeInterface` that is not one of them falls
through to rule 7 (a binding with the same *parameter* name) and then to
failure — bind it under the parameter's name:

```php
$router->bind('clock', new SystemClock());   // key = parameter name

$router->get('/now', fn (Clock $clock) => $clock->now());
```

## Injectable types

| Type | Source | Always available |
| --- | --- | --- |
| `RequestInterface` | the dispatch | yes |
| `ResponseInterface` | the dispatch | yes |
| `SessionInterface` | the session factory | yes (empty default) |
| `AuthInterface` | the auth binding or `Strukt\Auth` | yes (guest) |
| `FormInterface` | `Route::form()` | only when the route declares a form |

The session and auth gate are created per dispatch by `resolveSession()` and
`resolveAuth()`, so they are safe to share within one request and empty in the
next unless your factory persists state.

## Named bindings

`bind()` registers an instance or a factory. A `Closure` is called on every
resolution; anything else is handed out as-is:

```php
$router->bind('greeting', fn (): string => 'hello from the router');
$router->bind('clock', new SystemClock());
$router->bind('api.client', fn () => new Client(getenv('API_URL')));
```

- The **key** must equal the action parameter's name.
- Bindings are also visible to middleware constructors — by name *and*, for
  middleware, by type.

## Middleware constructor resolution

Middleware classes resolve richer than actions, because a class is being
constructed rather than invoked. For each constructor parameter:

1. binding of the same parameter name
2. the `verify` callable, when the parameter is named `$verify`
3. the `permissions` callable, when the parameter is named `$permissions`
4. typed `SessionInterface`
5. typed `AuthInterface`
6. binding of the same **type** (an interface or class name)
7. declared default
8. nullable parameter
9. otherwise `ResolutionException`

```php
$router->named('signed', SignerMiddleware::class);

final class SignerMiddleware implements MiddlewareInterface
{
    public function __construct(private SessionInterface $session) {}   // rule 4
}
```

Steps 4–6 are the difference from action resolution: middleware may be handed
services typed by interface without any name matching.

## The globals factory

When `handle()` is called with no request, the kernel needs one. It asks the
router:

```php
$router->globals(fn (): RequestInterface => Request::createFromGlobals());
```

- With a factory: that request is used.
- Without: `Request::createFromGlobals()` is used directly.

Either way `handle()` works with zero arguments, which is what makes the
front-controller style possible:

```php
$router->kernel()->send();
```

## When resolution fails

A parameter that matches nothing throws `ResolutionException`:

```
Unable to resolve parameter [config] of the route action.
```

The kernel catches it and returns **500** with that message — the route is
matched, the middleware ran, but the action never started. The usual fixes:

- add a default value (`$config = null`),
- register `bind('config', …)`,
- or correct the type to one of the injectable interfaces.

See [Troubleshooting](troubleshooting.md) for the full catalogue.

## Next steps

- [Middleware](middleware.md) for constructor injection in guards.
- [Testing routes](testing.md) for asserting injected values.
- [`examples/08-service-injection.php`](../examples/08-service-injection.php)
  for a runnable demonstration.
