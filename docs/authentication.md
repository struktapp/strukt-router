# Authentication and permissions

The router ships a session store, an auth gate, and two route options —
`requiresAuth()` and `permissions()` — that turn a route into a protected one.

- [The pieces](#the-pieces)
- [Configuring the factories](#configuring-the-factories)
- [Protecting a route](#protecting-a-route)
- [How the user is resolved](#how-the-user-is-resolved)
- [Logging in and out](#logging-in-and-out)
- [Permission mapping](#permission-mapping)
- [A worked flow](#a-worked-flow)
- [Session persistence across dispatches](#session-persistence-across-dispatches)

## The pieces

| Piece | Configured by | Provides |
| --- | --- | --- |
| Session store | `Router::session(fn (): SessionInterface)` | The per-client key/value store handed to middleware, actions, and the auth gate. |
| Auth gate | `Router::bind(AuthInterface::class, …)` or the default `Strukt\Auth` | `check()`, `guest()`, `user()`, `login()`, `logout()`. |
| User resolver | `Router::verify(fn (SessionInterface): ?UserInterface)` | Decides who is signed in for protected routes. |
| Permission resolver | `Router::permissions(fn (?UserInterface): array)` | The list of permission names a user holds. |

Without configuration the router still works: `resolveSession()` returns an
empty `Strukt\Session`, and `resolveAuth()` returns a `Strukt\Auth` backed by
it, so every request is a guest. Under a web server, bind the built-in
`Strukt\Http\Session\Native` store so the session survives across requests, or
`Strukt\Http\Session\Cache` when several processes or servers must share it.

## Configuring the factories

```php
use Strukt\Auth;
use Strukt\Contract\Http\SessionInterface;
use Strukt\Contract\UserInterface;
use Strukt\Http\Session\Native;
use Strukt\User;

$router->session(fn (): SessionInterface => new Native());

$router->verify(fn (SessionInterface $session): ?UserInterface => $session->get(Auth::SESSION_EMAIL) !== null
    ? new User(email: $session->get(Auth::SESSION_EMAIL))
    : null);

$router->permissions(fn (?UserInterface $user): array => $user?->getEmail() === 'admin@example.com'
    ? ['admin']
    : []);
```

- `verify()` receives the session, so it can look up a record, check a token,
  or rebuild a user from whatever your login stored.
- `permissions()` receives whatever `verify()` produced — or `null` for a
  guest — and must return an array of permission names.
- The gate itself is replaceable: bind `AuthInterface::class` (or the alias
  `auth`) to an object or a factory, and `resolveAuth()` will use it.

## Protecting a route

```php
$router->get('/dashboard', fn (RequestInterface $request) => $request->user()?->getEmail())
    ->requiresAuth();

$router->get('/admin', fn () => 'admin panel')
    ->requiresAuth()
    ->permissions(['admin']);
```

| Situation | Status | Body |
| --- | --- | --- |
| `requiresAuth()` and no user | 401 | `Authentication required!` |
| `permissions()` and a guest | 401 | `Authentication required!` |
| `permissions()` and a user without any required name | 403 | `Unauthorized access!` |
| `permissions()` and the user holds at least one required name | passes | action result |

Permission names are an **intersection test**: holding any one of the listed
names is enough.

## How the user is resolved

For routes that carry `requiresAuth()` or `permissions()`, the kernel resolves
the user **before** the middleware chain runs:

```
verify factory set?  → verify($session)
otherwise            → $auth->user()
```

The result is bound with `$request->setUser($user)`, so any middleware or
action can read it through `$request->user()`.

This is deliberately independent of the `auth` named middleware:

- `requiresAuth()` / `permissions()` — enforcement, kernel-wired, user
  resolved only for routes that need it.
- `->middleware('auth')` — convenience for routes that *display* something
  user-specific but are publicly reachable; it binds the user and lets the
  chain continue for guests.

They compose fine on the same route.

## Logging in and out

The auth gate writes the user into the session under the `Auth::SESSION_*`
keys:

```php
$router->post('/login', function (AuthInterface $auth, FormInterface $form): array {
    $auth->login(new User(
        username: $form->value('email'),
        email: $form->value('email'),
    ));

    return ['logged_in' => true];
})->form(LoginForm::class);

$router->post('/logout', function (AuthInterface $auth): array {
    $auth->logout();

    return ['logged_out' => true];
});
```

Two rules matter:

1. **`login()` requires an id or a username.** A user carrying neither throws
   `InvalidArgumentException` (surfaced as a 500). Give the record at least a
   username — or an id — when you construct it.
2. **Read the same keys back in `verify()`.** `Auth::login()` persists
   `Auth::SESSION_ID`, `SESSION_USERNAME`, `SESSION_EMAIL`, and
   `SESSION_TOKEN`; `Auth::logout()` removes them. Reading
   `Auth::SESSION_EMAIL` in `verify()` is the reliable pairing:

```php
use Strukt\Auth;

$router->verify(fn (SessionInterface $session): ?UserInterface => $session->get(Auth::SESSION_EMAIL) !== null
    ? new User(email: $session->get(Auth::SESSION_EMAIL))
    : null);
```

If your login writes its own key instead, read *that* key — but then it must
be the same key your login stored, or every protected route stays a 401.

## Permission mapping

`permissions()` is a single place to map a user to grants:

```php
$router->permissions(function (?UserInterface $user): array {
    if ($user === null) {
        return [];
    }

    return match ($user->getEmail()) {
        'admin@example.com' => ['admin', 'editor'],
        'editor@example.com' => ['editor'],
        default => ['read'],
    };
});
```

Routes then ask only for what they need:

```php
$router->get('/posts/new', fn () => 'form')->requiresAuth()->permissions(['editor']);
$router->get('/settings', fn () => 'settings')->requiresAuth()->permissions(['admin']);
```

The gate is appended **after** the route's own middleware, so header logging
or session attachment already ran by the time access is denied.

## A worked flow

The complete sequence, guest → editor → admin, is in
[`examples/06-authentication.php`](../examples/06-authentication.php):

```
guest   GET  /dashboard  → 401  Authentication required!
guest   GET  /admin      → 401  Authentication required!
        POST /login      → 200  {"logged_in":true,…}
editor  GET  /dashboard  → 200  hi ada@example.com
editor  GET  /admin      → 403  Unauthorized access!
        POST /login      → 200  {"logged_in":true,…}   (admin)
admin   GET  /admin      → 200  panel of admin@example.com
```

## Session persistence across dispatches

`Router::services()` calls the session factory **once per dispatch**, so a
factory that builds a fresh store gives every request a clean slate. For a web
request that fresh store is `Strukt\Http\Session\Native`, which reads and writes
`$_SESSION` directly, so the clean slate still holds the client's persisted
values.

When several processes or servers must share the session, bind
`Strukt\Http\Session\Cache` instead. It rehydrates from a cache driver
(memcached, the filesystem, or memory) and writes through on every mutation, so
the fresh store still starts from the client's persisted values:

```php
$router->session(fn (): SessionInterface => new Cache(memc('sess')));
```

Give each client its own driver namespace (`memc('sess_' . $sid)`) to keep
sessions isolated. See [`http-layer.md`](http-layer.md#session-store) and
[`installation.md`](installation.md#requirements) for the optional
`strukt/cache` dependency.

For a CLI script or a test that performs several dispatches and expects state
to carry over, capture one store and return it:

```php
$store = new Session();
$router->session(fn (): SessionInterface => $store);
```

Every dispatch then shares the same store, so a login in one request is
visible to the next — which is how the example works.

## Next steps

- [Forms and validation](forms.md) for the login form used above.
- [Middleware](middleware.md) for the built-in `sess`, `auth`, `authz` guards.
- [Responses and errors](responses.md) for the full status-code mapping.
