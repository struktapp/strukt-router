# Routing

Every route is a verb method plus a pattern plus an action. The verb methods
all share the same shape and all return the registered `Route`, so options
stack fluently after the declaration.

- [Verb methods](#verb-methods)
- [Patterns](#patterns)
- [Groups](#groups)
- [Named routes and reverse generation](#named-routes-and-reverse-generation)
- [Route options](#route-options)
- [How a request is matched](#how-a-request-is-matched)
- [Route defaults](#route-defaults)

## Verb methods

| Method | Accepts |
| --- | --- |
| `get()` | `GET` |
| `post()` | `POST` |
| `put()` | `PUT` |
| `patch()` | `PATCH` |
| `delete()` | `DELETE` |
| `options()` | `OPTIONS` |
| `head()` | `HEAD` |
| `any()` | `GET`, `POST`, `PUT`, `PATCH`, `DELETE`, `OPTIONS`, `HEAD` |

```php
$router->get('/list', fn () => 'list');
$router->post('/save', fn () => 'saved');
$router->any('/hook', fn () => 'any method');
```

Actions may be closures, function names, or `[object, 'method']` pairs;
`Router::register()` normalises anything invokable with
`Closure::fromCallable()`.

## Patterns

A pattern is slash-separated segments. A segment is one of:

| Form | Matches | Example |
| --- | --- | --- |
| literal | exactly itself | `/health` |
| `{name}` | any single segment, non-empty, no slash | `/user/{name}` |
| `{name:type}` | one of the typed languages below | `/user/{id:int}` |
| `{rest:*}` | everything remaining, including slashes | `/files/{rest:*}` |
| `{?name:type}` | an optional segment (may be omitted) | `/blog/{year:int}/{?month:int}` |

### Types

| Type | Matches | Extracted as |
| --- | --- | --- |
| `int` | digits | `int` |
| `float` | decimal, optional sign | `float` |
| `bool` | `true` or `false` | `bool` |
| `alpha` | letters only | `string` |
| `alphanum` | letters, digits, `_`, `-` | `string` |
| `slug` | lowercase words joined by `-` | `string` |
| `date` | `YYYY-MM-DD` | `string` |
| `uuid` | a UUID | `string` |

```php
$router->get('/user/{id:int}', fn (int $id) => "id={$id}");
$router->get('/price/{value:float}', fn (float $value) => $value * 2);
$router->get('/post/{slug}', fn (string $slug) => $slug);
$router->get('/files/{rest:*}', fn (string $rest) => $rest);
$router->get('/blog/{year:int}/{?month:int}', fn (int $year, ?int $month = null) => $month);
```

Notes:

- Values are **cast on extraction**: `{id:int}` hands an `int` to the action,
  `{on:bool}` a `bool` through `filter_var(..., FILTER_VALIDATE_BOOL)`.
- An **unknown type** throws `InvalidArgumentException` when the pattern is
  compiled (at match time), which the kernel reports as a 500 — see
  [Troubleshooting](troubleshooting.md).
- `/user/abc` simply does not match `{id:int}`, so it falls through to the 404
  route rather than being rejected by the route.
- Pattern text is normalised: leading and trailing slashes are forced, and the
  root pattern `/` matches only `/`.

## Groups

`group()` opens a scope whose routes share a path prefix. The callback
receives the router, and the prefix stack pops even if the callback throws.

```php
$router->group('/api/v1', function (Router $router): void {
    $router->get('/users', fn () => 'list')->name('api.users');
    $router->get('/users/{id:int}', fn (int $id) => "user {$id}")->name('api.users.show');
});

$router->path('api.users');                        // '/api/v1/users'
$router->match('/api/v1/users/42', 'GET')['params'];  // ['id' => 42]
```

Groups nest, and prefixes join without double slashes:

```php
$router->group('/api', function (Router $router): void {
    $router->group('/v1', function (Router $router): void {
        $router->get('/ping', fn () => 'pong');   // registered as /api/v1/ping
    });
});
```

## Named routes and reverse generation

`name()` labels a route; `path()` rebuilds a URI from the label:

```php
$router->get('/user/{id:int}', fn (int $id) => "user {$id}")
    ->name('user.show');

$router->path('user.show', ['id' => 7]);   // '/user/7'
```

- The name index is written when the route is registered. A name assigned
  *after* registration is still found, by a fallback scan of the collection.
- An unknown name throws `ResolutionException` (`Route [x] was not found.`).
- A missing required parameter throws `InvalidArgumentException`
  (`Missing required parameter [id] for route [/user/{id:int}].`).

Because groups apply their prefix at registration time, `path()` returns the
full prefixed URI automatically.

## Route options

`Route` exposes six fluent mutators. They apply to the registered route in
place and can be stacked in any order:

| Option | Sets | Read back with |
| --- | --- | --- |
| `name(string)` | the reverse-generation name | `getName()` |
| `middleware(...)` | route middleware to append | `getMiddleware()` |
| `permissions(array)` | permission names the action requires | `getPermissions()` |
| `requiresAuth()` | the route needs an authenticated user | `authRequired()` |
| `form(string)` | the form class validated before the action | `getForm()` |
| `defaults(array)` | values for reverse generation | `getDefaults()` |

```php
$router->get('/admin', fn () => 'admin')
    ->name('admin.home')
    ->middleware('sess', 'auth')
    ->requiresAuth()
    ->permissions(['admin']);
```

`middleware()` accepts names, classes, objects, callables, and arrays of
those — see [Middleware](middleware.md).

## How a request is matched

Matching is registration order, first match wins:

1. `RouteCollection::match()` collects every route whose pattern accepts the
   path.
2. The **first** collected route that also accepts the request method wins.
3. No pattern matched → `RouteNotFoundException` → **404**.
4. Pattern matched but no method did → `MethodNotAllowedException` → **405**
   with an `Allow` header listing the union of acceptable methods.

Practical consequences:

- Declare specific routes **before** generic ones:
  `/admin/new` must precede `/admin/{id:int}`.
- Two routes can share a pattern with different methods, and the first one
  that allows the method wins:

```php
$router->get('/thing', fn () => 'read');
$router->post('/thing', fn () => 'write');
$router->put('/thing', fn () => 'replace');   // all three are reachable
```

- `match()` is public, so you can resolve a path yourself without dispatching:

```php
$match = $router->match('/user/7', 'GET');
$match['route'];    // Route
$match['params'];   // ['id' => 7]
```

## Route defaults

`defaults()` fills placeholders that are absent from the parameters passed to
reverse generation. It is the natural companion of an optional segment:

```php
$router->get('/blog/{year:int}/{?month:int}', fn (int $year, ?int $month = null) => '…')
    ->name('blog.show')
    ->defaults(['month' => 1]);

$router->path('blog.show', ['year' => 2026]);            // '/blog/2026/1'
$router->path('blog.show', ['year' => 2026, 'month' => 7]); // '/blog/2026/7'
```

Defaults are merged **under** the supplied parameters, so an explicit value
always wins. They do not affect matching — only `path()` and `Route::uri()`.

## Next steps

- [Middleware](middleware.md) for guarding routes.
- [Dependency injection](dependency-injection.md) for what the action receives.
- [Responses and errors](responses.md) for return values and status codes.
