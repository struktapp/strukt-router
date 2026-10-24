# API reference

Every public class and method, with the exact signature and behaviour. Read
the pages it links to for the reasoning behind each method.

- [Strukt\Router\Router](#struktrouterrouter)
- [Strukt\Router\Route](#struktrouterroute)
- [Strukt\Router\RouteCollection](#struktrouterroutecollection)
- [Strukt\Router\UrlMatcher](#struktrouterurlmatcher)
- [Strukt\Router\Kernel](#struktrouterkernel)
- [Strukt\Router\Invoker](#struktrouterinvoker)
- [Strukt\Router\Cli\RouteLister](#struktroutercliroutelister)
- [Strukt\Router\Middleware\Session](#struktroutermiddlewaresession)
- [Strukt\Router\Middleware\Authentication](#struktroutermiddlewareauthentication)
- [Strukt\Router\Middleware\Authorization](#struktroutermiddlewareauthorization)
- [Strukt\Router\Exception\*](#struktrouterexception)

## Strukt\Router\Router

The registry and DSL. All mutators return `static` for fluent stacking.

### Registration

| Signature | Returns | Notes |
| --- | --- | --- |
| `get(string $pattern, callable $action)` | `Route` | registers `GET` |
| `post(...)` / `put(...)` / `patch(...)` / `delete(...)` / `options(...)` / `head(...)` | `Route` | one verb each |
| `any(string $pattern, callable $action)` | `Route` | `GET, POST, PUT, PATCH, DELETE, OPTIONS, HEAD` |
| `group(string $prefix, callable $group)` | `void` | the callback receives a `Router`; registered patterns gain the prefix |
| `middleware(object\|callable\|string\|array ...$definitions)` | `static` | global middleware, in argument order |
| `named(string $name, object\|callable\|string $definition)` | `static` | register a reusable middleware name |

### Service configuration

| Signature | Returns | Behaviour |
| --- | --- | --- |
| `bind(string $id, object $resolver)` | `static` | a `Closure` is invoked per resolution; anything else is handed out as-is |
| `session(callable $factory)` | `static` | `fn(): SessionInterface`; default is `new Strukt\Session()` |
| `verify(callable $factory)` | `static` | `fn(SessionInterface): ?UserInterface`; default reads the auth gate |
| `permissions(callable $factory)` | `static` | `fn(?UserInterface): array`; default returns `[]` |
| `globals(callable $factory)` | `static` | `fn(): RequestInterface` used when `handle()` gets no request |

### Resolving

| Signature | Returns | Notes |
| --- | --- | --- |
| `resolveSession(): SessionInterface` | the factory result or a new empty store | throws `ResolutionException` if the factory returns a non-session |
| `resolveAuth(SessionInterface $session): AuthInterface` | `bindings[AuthInterface::class]`, else `bindings['auth']`, else `new Strukt\Auth($session)` | throws if the binding is not an `AuthInterface` |
| `resolve(string $id): mixed` | the bound value or factory result | `null` when nothing is bound |

### Inspecting

| Signature | Returns |
| --- | --- |
| `services(): array` | `['session', 'auth', 'verify', 'permissions', 'bindings']` |
| `makeMiddleware(object\|callable\|string $middleware, array $services): MiddlewareInterface` | a resolved instance; throws `ResolutionException` on unresolved input |
| `match(string $path, string $method): array` | `['route' => Route, 'params' => array]`; throws `RouteNotFoundException` / `MethodNotAllowedException` |
| `path(string $name, array $params = []): string` | the reconstructed URI; throws `ResolutionException` for an unknown name |
| `middlewareList(): array` | global middleware definitions |
| `namedMiddleware(): array` | the name-indexed middleware map (defaults to `sess`, `auth`, `authz`) |
| `bindings(): array` | the name/interface → resolver map |
| `globalsFactory(): ?Closure` | the request factory, or `null` |
| `collection(): RouteCollection` | the route collection |

### Dispatch

| Signature | Returns |
| --- | --- |
| `kernel(): Kernel` | a new `Kernel` bound to this router |

## Strukt\Router\Route

One registered route. Returned by the verb methods so route options can be
stacked. All option mutators return `static`.

| Signature | Returns / throws |
| --- | --- |
| `__construct(string\|array $methods, string $pattern, callable $action)` | direct construction (normally bypassed by the DSL) |
| `methods(): array` | uppercased, de-duplicated method list |
| `pattern(): string` / `action(): callable` | accessors |
| `name(string): static` / `getName(): ?string` | reverse-generation name |
| `middleware(object\|callable\|string\|array): static` / `getMiddleware(): array` | route middleware, appended |
| `permissions(array): static` / `getPermissions(): array` | permission names required by the route |
| `requiresAuth(): static` / `authRequired(): bool` | forces a 401 for guests |
| `form(string): static` / `getForm(): ?string` | the form class submitted/validated pre-action |
| `defaults(array): static` / `getDefaults(): array` | defaults merged at reverse generation |
| `allows(string $method): bool` | case-insensitive method check |
| `params(string $path): ?array` | typed params, or `null` when the path does not match |
| `uri(array $params = []): string` | reversed URI; throws `InvalidArgumentException` for a missing required parameter |

## Strukt\Router\RouteCollection

Ordered route store.

| Signature | Returns |
| --- | --- |
| `add(Route $route): Route` | registers and returns the route |
| `all(): array` | routes in registration order |
| `count(): int` | registered route count |
| `byName(string $name): ?Route` | the named route, or `null` |
| `match(string $path, string $method): array` | first route accepting both; throws as documented |
| `allowedMethods(string $path): array` | union of methods across routes accepting the path |

## Strukt\Router\UrlMatcher

Pattern compiler, extractor, and reverse generator. Constructed with an
optional pattern list:

```php
$matcher = new UrlMatcher(['/user/{id:int}', '/blog/{?slug}']);
```

| Signature | Returns / throws |
| --- | --- |
| `compile(string $pattern): array` | `['regex', 'keys', 'parts']`; throws `InvalidArgumentException` for an unknown type |
| `isMatch(string $pattern, string $path): bool` | equality with `params() !== null` |
| `params(string $pattern, string $path): ?array` | typed, cast params or `null` |
| `which(string $path): ?string` | first pattern in the list accepting `$path`; records params for `lastParams()` |
| `lastParams(): array` | params from the most recent `which()` |
| `uri(string $pattern, array $params = []): string` | reversed URI; optional placeholders dropped when absent; throws `InvalidArgumentException` for a missing required value |

Supported types: `int`, `float`, `bool`, `alpha`, `alphanum`, `slug`, `date`,
`uuid`. Untyped `{name}` matches `[^/]+`; `{rest:*}` catches everything after
it; `{?x:int}` marks an optional segment.

## Strukt\Router\Kernel

Holds a router reference; dispatches requests to responses and maps every
thrown error to an HTTP response.

| Signature | Returns | Notes |
| --- | --- | --- |
| `__construct(Router $router)` | — | normally obtained via `Router::kernel()` |
| `handle(?RequestInterface $request = null, ?ResponseInterface $response = null): ResponseInterface` | response | builds a fresh `Response` when none is given; catches `Throwable` and returns the mapped error; never throws |
| `send(?RequestInterface $request = null): void` | — | `handle()->send()` |

Dispatch stages: globals → match → services → middleware steps → resolve user
(auth gates only) → 401 if required-but-missing → append `Authorization` gate
→ run chain → `Invoker::invoke()` → `wrap()`.

`wrap()` accepts `ResponseInterface` (as-is), `DownloadInterface` (mime +
length + disposition headers, body = contents), `string` (body), `array`
(JSON via `setJson()`), and `null` (empty body). Anything else throws
`ResolutionException`.

## Strukt\Router\Invoker

Reflects an action and resolves its parameters.

| Signature | Returns / throws |
| --- | --- |
| `invoke(Route $route, array $params, RequestInterface $request, ResponseInterface $response, array $services): mixed` | the action result; throws `ResolutionException` or `ValidationException` |

Resolution order per parameter: matched route param by name → `RequestInterface`
→ `ResponseInterface` → `SessionInterface` → `AuthInterface` → `FormInterface`
(route form, submitted and validated) → named binding → default → nullable →
`ResolutionException`.

## Strukt\Router\Cli\RouteLister

Reads a routes bootstrap file and renders the registered routes as an aligned
table; it backs the `routes` binary.

| Signature | Returns / throws |
| --- | --- |
| `static load(string $file): Router` | the router the file returns; throws `InvalidArgumentException` when the file is missing or returns anything else |
| `__construct(Router $router)` | — |
| `rows(): array` | `list<array{methods: string, pattern: string, name: string, auth: string}>` in registration order |
| `render(): string` | aligned table ending in a newline, or `No routes registered.` when empty |

## Strukt\Router\Middleware\Session

| Signature | Notes |
| --- | --- |
| `__construct(SessionInterface $session)` | `sess` |
| `process(...): ResponseInterface` | attaches the store to a concrete `Strukt\Http\Request` via `setSession()`; a non-`Request` passes through |

## Strukt\Router\Middleware\Authentication

| Signature | Notes |
| --- | --- |
| `__construct(AuthInterface $auth, ?callable $verify = null, ?SessionInterface $session = null)` | `auth` |
| `process(...): ResponseInterface` | resolves the user — the `verify` resolver when both it and a session are present, otherwise `$auth->user()` — and `setUser()`s it on the request; a guest leaves the request userless but the chain continues |

## Strukt\Router\Middleware\Authorization

| Signature | Notes |
| --- | --- |
| `__construct(callable $resolver, array $require = [])` | `authz` |
| `process(...): ResponseInterface` | with an empty `$require` it passes through; otherwise it intersects the resolver's grant against `$require` and throws `AuthorizationException` — `Authentication required!` / 401 for a guest, `Unauthorized access!` / 403 for a denied user |

## Strukt\Router\Exception\*

| Exception | Extends | Default code | Carries |
| --- | --- | --- | --- |
| `RouteNotFoundException` | `RuntimeException` | 404 | — |
| `MethodNotAllowedException` | `RuntimeException` | 405 | `allowedMethods(): array` |
| `AuthorizationException` | `RuntimeException` | 401 | — (403 is passed by the gate) |
| `ResolutionException` | `RuntimeException` | 0 | — (mapped to 500 by the kernel) |
| `ValidationException` | `RuntimeException` | 422 | `errors(): array` |

The kernel maps each to its status and message; see
[Responses and errors](responses.md#error-mapping).

## Next steps

- The contract interfaces live in `strukt4/contract` — this package never
  type-hints its concrete classes.
- The helper functions wrap the classes above; see [Global helpers](helpers.md).