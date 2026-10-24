# Troubleshooting

`handle()` never throws — every failure comes back as a response. The status
plus the body text identify the problem almost every time.

- [Reading a failure](#reading-a-failure)
- [500: internal errors](#500-internal-errors)
- [404: not found](#404-not-found)
- [405: wrong method](#405-wrong-method)
- [401 / 403: authentication](#401--403-authentication)
- [422: validation](#422-validation)
- [Middleware quirks](#middleware-quirks)
- [Helpers and helpers](#helpers-and-setup)
- [Debugging toolkit](#debugging-toolkit)

## Reading a failure

```php
$response = $router->kernel()->handle(new Request('GET', '/thing'));

printf("%d %s\n%s\n", $response->status(), $reason($response->status()), $response->body());
```

The body is the message — the kernel puts the failure reason there verbatim.

## 500: internal errors

| Body contains | Cause | Fix |
| --- | --- | --- |
| `Unable to resolve parameter [x] of the route action.` | the action declares a parameter nothing supplies | add a default (`$x = null`), `bind('x', …)`, or correct the type — see [dependency injection](dependency-injection.md) |
| `Route actions must return a string, an array, or a ResponseInterface.` | the action returned an int, object, etc. | return a supported value (also covered: `DownloadInterface`, `null`) |
| `Unable to resolve middleware parameter [x] of [Class].` | a middleware constructor parameter is unresolvable | add a default, a binding, or the expected service |
| `Middleware class [X] was not found.` | a name that is not autoloadable was passed to `middleware()` | check the FQCN |
| `Middleware [X] must implement the MiddlewareInterface.` | wrong interface | implement `Strukt\Contract\Http\MiddlewareInterface` |
| `Middleware must be a name, a class, an object, or a callable.` | wrong argument shape | pass one of the four supported forms |
| `The route does not declare a form class to resolve.` | the action has a `FormInterface` parameter but no `->form(…)` was set | call `->form(LoginForm::class)` on the route |
| `Unsupported parameter type [y] in route segment [{x:y}].` | unknown URL type | use a type from the [routing table](routing.md#typed-parameters) |
| `The session factory must return a SessionInterface.` | `->session(fn () => …)` returned something else | return a `SessionInterface` |
| `The auth binding must satisfy the AuthInterface.` | `bind('auth', …)` returned a non-auth | return an `AuthInterface` |
| `A user requires an id or a username to authenticate!` | your `verify()` built a `Strukt\User` with neither | pass `id:` or `username:` when constructing it |
| `Missing required parameter [id] for route …` | `path()` was called without all parameters | pass every required parameter |
| `Route [name] was not found.` | reverse generation for an unknown name | check `name()` spelling / registration order |
| `Unknown validation rule [x]. Registered rules: …` | a rule string references an unregistered name | fix the typo or `RuleRegistry::extend()` it |

## 404: not found

- **The parameter's cast failed.** `/user/abc` does not match
  `/user/{id:int}`, so the route never matched. Either relax the type or
  expect 404.
- **Group prefixes.** Routes registered inside `group('/api', …)` are only
  reachable at `/api/...`.
- **Path vs query.** The router matches `path()` only; `/search?q=x` matches
  `/search`.
- **Registration order.** The first matching route wins; a route registered
  later is never tried once an earlier one accepts both the path and the
  method. Reorder, or narrow the earlier pattern.
- **Trailing slashes.** Patterns are normalised as authored — if you register
  `/users`, call `/users/` and you may get a 404.

## 405: wrong method

The path matched, but no route claimed that verb. The response carries
`Allow:` with the union of allowed methods — read it:

```php
$response->header('Allow');   // 'GET, POST'
```

Common causes: sending `POST` to a `get()` route, a missing `any()`, or a
`group()` whose callback registered fewer verbs than the client uses.

## 401 / 403: authentication

- **401 with `Authentication required!`** — either the route has
  `requiresAuth()`/`verify()` and `$request->user()` is null, or a
  `permissions()` gate saw a guest. Either the login did not persist, or
  `verify()` returned null.
- **The classic `SESSION_EMAIL` mismatch.** `Strukt\Auth::login()` stores
  the email under `Auth::SESSION_EMAIL`. A `verify()` that reads a different
  key always sees a guest. Use the constants.
- **The session factory builds a new store each call.** If your factory is
  `fn () => new Session()`, every dispatch starts empty — including the one
  that checks the user. Share one instance inside the process (see
  [session persistence](authentication.md#session-persistence-across-dispatches)).
- **403 with `Unauthorized access!`** — the user exists but
  `permissions()` returned `[]` (or an array without the required role).
  Check what your resolver returns for that user.

## 422: validation

The action **did not run** — the kernel answered before invoking it. The body
is JSON:

```php
$errors = json_decode($response->body(), true)['errors'];
```

- Fields absent from the submission fail `required`.
- `min`/`max`/`between` compare **numbers**; text length is
  `min_length`/`max_length`.
- A blank optional field skips its other rules — a blank value with no
  `required` produces *no* error.

If you expected a 500 for a bad rule, see the `Unknown validation rule`
message above: it is thrown while parsing rules, not collected as a field
error.

## Middleware quirks

- **Middleware does not run on 404/405.** The match throws before the chain
  is built — by design, so auth cannot mask a missing route.
- **Order is registration order**, global middleware first, then route
  middleware. A `finally`-style cleanup in `after` still runs on failures
  inside the chain, but not on the match failures above.
- **A middleware that returns a `Response`** ends the chain for that
  direction (the return value is captured); throwing ends the dispatch with
  the mapped status.

## Helpers and setup

- **`Call to undefined function router()`** — the helpers are autoloaded via
  Composer `files`; run `composer dump-autoload` after adding the package.
  Defining your own `router()` in application code also suppresses the
  built-in one (by design).
- **Helpers exist but are not yours** — every helper registers with the Strukt
  helper registry (`helper_add`), so a conflicting application helper must be
  declared *before* `vendor/autoload.php` is required.

## Debugging toolkit

Inspect rather than guess:

```php
$router = new Router();
$router->get('/user/{id:int}', fn (int $id) => "user {$id}");

// 1. does it match at all? (throws when it does not — catch to inspect)
try {
    $match = $router->match('/user/7', 'GET');
    $match['route'];    // Route
    $match['params'];   // ['id' => 7]
} catch (RouteNotFoundException) {
    // nothing matched the path
} catch (MethodNotAllowedException $e) {
    $e->allowedMethods();   // ['GET']
}

// 2. what is registered?
$router->collection()->count();
$router->collection()->all();                          // the Route objects

// 3. what methods would this path allow (without throwing)?
$router->collection()->allowedMethods('/user/7');      // ['GET']

// 4. reverse generation
$router->path('user.show', ['id' => 7]);               // '/user/7'
```

`match()` takes a **path and a method** — no request object needed. It throws
`RouteNotFoundException` or `MethodNotAllowedException` when there is no
match, so wrap it in a try/catch to inspect the parameters of a successful
match.

## Next steps

- [Architecture](architecture.md) for why a stage behaves this way.
- [API reference](api-reference.md) for the exact signatures involved.
