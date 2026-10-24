# Architecture

This document explains how Strukt Router is organised, why it is layered the
way it is, and how a request travels from `handle()` to `send()`.

- [Layers at a glance](#layers-at-a-glance)
- [The dispatch pipeline](#the-dispatch-pipeline)
- [Component responsibilities](#component-responsibilities)
- [Matching and registration](#matching-and-registration)
- [The middleware chain](#the-middleware-chain)
- [Action invocation](#action-invocation)
- [Exception mapping](#exception-mapping)
- [Package boundaries](#package-boundaries)
- [Helpers and the Strukt registry](#helpers-and-the-strukt-registry)
- [Design decisions](#design-decisions)

## Layers at a glance

```
┌───────────────────────────────────────────────────────────────┐
│  Global helpers   router  request  response  route  matcher   │
│                   redirect  download                          │
├───────────────────────────────────────────────────────────────┤
│  Dispatch         Kernel::handle / send                       │
├───────────────────────────────────────────────────────────────┤
│  Routing          Router  Route  RouteCollection              │
│  Matching         UrlMatcher (compile / params / uri)         │
├───────────────────────────────────────────────────────────────┤
│  Pipeline         Middleware\Session | Authentication |       │
│                   Authorization + callable wrappers           │
│  Invocation       Invoker (reflection argument resolution)    │
├───────────────────────────────────────────────────────────────┤
│  HTTP layer       Request  Response  ResponseBuilder          │
│                   Download  UploadedFile                      │
├───────────────────────────────────────────────────────────────┤
│  Contracts        strukt4/contract: RequestInterface,         │
│                   ResponseInterface, MiddlewareInterface,     │
│                   SessionInterface, AuthInterface,            │
│                   FormInterface, UserInterface                │
└───────────────────────────────────────────────────────────────┘
```

Each layer only depends on the ones beneath it:

- The **contracts** describe what a request, a response, a middleware, a
  session, an auth gate, a form, and a user are, without saying how they are
  implemented. Everything the router hands to your code comes from
  `strukt4/contract`.
- The **HTTP layer** implements those contracts with plain arrays and value
  objects, so a request can be built from `$_SERVER`, from a test, or by hand.
- The **routing layer** keeps the declared routes (`RouteCollection`), turns
  patterns into regular expressions (`UrlMatcher`), and exposes the fluent DSL
  (`Router`).
- The **pipeline layer** composes middleware and reflects the action.
  `Kernel` orchestrates it.
- The **helpers** are thin aliases over all of the above, registered through the
  Strukt helper registry.

## The dispatch pipeline

Everything funnels through `Kernel::handle()`. One call, in order:

```
Kernel::handle($request, $response)
│
├─ 1. resolve globals           request ??= globals() factory ?? Request::createFromGlobals()
│                               response ??= new Response()
│
├─ 2. match                      router->match($request->path(), $request->method())
│   │                            throws RouteNotFoundException (404)
│   │                            throws MethodNotAllowedException (405)
│   └─ returns ['route' => Route, 'params' => array<string,mixed>]
│
├─ 3. services                   router->services()
│   │                            { session, auth, verify, permissions, bindings }
│
├─ 4. middleware steps           [...global, ...route] → makeMiddleware() each
│
├─ 5. resolve user               only when requiresAuth() or permissions() are set
│   │                            verify($session) ?: auth->user()
│   ├─ bind user onto request    $request->setUser($user)
│   └─ requiresAuth && no user → AuthorizationException (401)
│
├─ 6. permission gate            if permissions() != [] append
│                                new Authorization($services['permissions'], $permissions)
│
├─ 7. run the chain              recursion: each step process(req, res, $next)
│
├─ 8. invoke the action          Invoker::invoke(route, params, request, response, services)
│                                form submission + validation happens here
│
├─ 9. wrap the result            ResponseInterface | DownloadInterface | string
│                                | array | null  →  ResponseInterface
│
└─ catch (Throwable)             error() → a Response, never an exception
```

Two consequences follow from this shape:

- **Middleware never runs for a 404 or 405.** The match throws before the
  chain is built, so unmatched paths are answered directly by `error()`.
- **The request and response objects are carried by reference.** Middleware and
  the action all see the same instances, which is why a middleware can set a
  header the action later reads, and vice versa.

## Component responsibilities

| Component | Owns | Does not own |
| --- | --- | --- |
| `Router` | Route registration, the DSL, named middleware, service bindings, factories, the `match`/`path` facade | Dispatch, exception mapping |
| `Route` | One route's methods, pattern, action, and the six fluent options | Matching logic |
| `RouteCollection` | Registration order, name index, `match()` and `allowedMethods()` | Parameter casting (delegated to `UrlMatcher`) |
| `UrlMatcher` | Pattern compilation, typed extraction, reverse generation | Route identity, method checks |
| `Kernel` | The pipeline, user resolution, exception mapping, result wrapping | Route storage |
| `Invoker` | Reflection over the action, argument resolution, form submission | Middleware construction |
| `Middleware\*` | The three built-in guards | General dispatch |

`Router::kernel()` builds a fresh `Kernel` per call, and `Kernel::handle()`
holds no state between calls, so a single `Router` can serve any number of
sequential dispatches.

## Matching and registration

Routes are kept in **registration order**. `RouteCollection::match()` works in
two passes:

1. Collect every route whose `UrlMatcher::params()` returns non-null for the
   path.
2. Return the first of those that `allows()` the request method.

If the path matches nothing at all, it throws `RouteNotFoundException` (404).
If the path matches but no collected route allows the method, it throws
`MethodNotAllowedException` (405) carrying the union of the acceptable methods
— which `Kernel` renders as the `Allow` header.

Pattern compilation lives in `UrlMatcher::compile()` and runs in three stages:

```
/user/{id:int}/posts/{?page:int}
        │
        ▼
normalize   trim, force a leading slash, split on /
        │
        ▼
part        literal → preg_quote
            {name}          → (?<name>[^/]+)
            {name:type}     → (?<name><TYPE>)
            {name:*}        → (?<name>.*)            (wildcard)
            {?name:type}    → (?:/(?<name><TYPE>))?  (optional)
        │
        ▼
assemble    '#^/user/(?<id>[0-9]+)/posts(?:/(?<page>[0-9]+))?$#'
```

Extraction casts values on the way out: `{id:int}` hands an `int` to the
action, `{price:float}` a `float`, `{on:bool}` a `bool` via
`filter_var(..., FILTER_VALIDATE_BOOL)`, and everything else stays a string.

## The middleware chain

Steps are built as a flat list and executed by recursion:

```
[ TimerMiddleware, TraceMiddleware, closure, GateMiddleware ]
        │
        ▼
run() pops the first step, wraps the rest as $next,
and calls process($request, $response, $next)
```

Definitions are resolved by `Router::makeMiddleware()`:

| Definition | Resolution |
| --- | --- |
| Name (`'sess'`) | Looked up in the named table, then re-resolved. |
| Class string | `new $class(...)` with constructor injection from the services. |
| Object implementing `MiddlewareInterface` | Used as-is. |
| Any other callable | Wrapped in an anonymous `MiddlewareInterface` adapter. |

Constructor injection for middleware is richer than for actions: it consults
bindings by parameter name, the `verify`/`permissions` callables, typed
`SessionInterface`/`AuthInterface`, bindings **by type**, defaults, and
nullables — in that order (`Router::instantiate()`).

The permission gate is appended **last**, so it evaluates after global and
route middleware have run and can stop the chain before the action.

## Action invocation

`Invoker::invoke()` reflects the action with `Strukt\Ref::func()` and resolves
each parameter independently:

```
1. matched route parameter of the same name     $id from /user/{id:int}
2. RequestInterface / ResponseInterface          the carried pair
3. SessionInterface / AuthInterface              the per-request services
4. FormInterface                                submit + validate the route form
5. named binding (parameter name)                router->bind('clock', …)
6. declared default value                        fn (int $page = 1)
7. nullable parameter                            fn (?Config $config = null)
8. ResolutionException                           → 500
```

Actions receive a **closure**. `Router::register()` converts other callables
with `Closure::fromCallable()`, so a function name or a `[object, 'method']`
pair works too.

## Exception mapping

The kernel never lets an exception escape `handle()`. `Kernel::error()` maps
it:

| Exception | Status | Extra | Body |
| --- | --- | --- | --- |
| `RouteNotFoundException` | 404 | — | `Not found!` |
| `MethodNotAllowedException` | 405 | `Allow: GET, POST` | `Method not allowed!` |
| `AuthorizationException` | 401 or 403 | — | `Authentication required!` / `Unauthorized access!` |
| `ValidationException` | 422 | `Content-Type: application/json` | `{"errors":{"field":["…"]}}` |
| `ResolutionException` | 500 | — | `Unable to resolve parameter […]` |
| any other `Throwable` | 500 | — | the exception message |

The 422 branch is checked first because it needs a JSON body; afterwards an
explicit exception code is honoured when it is a plausible status (100–599),
and anything else falls back to 500. That is why `ResolutionException` (whose
default code is 0) becomes a 500.

## Package boundaries

```
strukt/router
├── strukt/contract   the interfaces + Strukt\Session, Strukt\Auth, Strukt\User
├── strukt/form       Strukt\Form\Form, Rule, RuleRegistry, Validator
├── strukt/commons    the helper registry (helper_add, reg) used by helpers.php
└── strukt/base       shared base utilities
```

The router has **no framework dependency and no web-server dependency**: the
HTTP layer is implemented inside this package against the contracts, and
`Request::createFromGlobals()` is the only place that touches `$_SERVER`.

## Helpers and the Strukt registry

`src/helpers.php` is autoloaded through Composer's `files` directive. Every
helper:

1. checks `function_exists()` first, so application-defined globals win;
2. registers itself with the Strukt helper registry via `helper_add()`;
3. delegates to a class — never implements logic inline.

| Helper | Delegates to |
| --- | --- |
| `router()` | `reg('router')` — one shared `Router` |
| `request(...)` | `new Request(...)` (defaults from the globals) |
| `response(...)` | `new ResponseBuilder(...)` |
| `route(...)` | `router()->kernel()->handle(request(...))` |
| `matcher(...)` | `new UrlMatcher(...)` |
| `redirect(...)` | `response($status)->redirect($url)` |
| `download(...)` | `new Download(...)` |

## Design decisions

- **Typed patterns, not regex per route.** `{id:int}` and friends compile to a
  single named-capture regex with a documented type table, so patterns are
  readable, cast on extraction, and reusable for reverse generation.
- **Registration order decides matches.** First match wins, so route order is
  meaningful and explicit — declare `/admin/new` before `/admin/{id:int}`.
- **Errors are responses, not exceptions.** `handle()` always returns a
  response, which makes dispatch safe to call from anywhere (including tests)
  without try/catch.
- **Reflection-based injection by name.** Actions are closures whose parameter
  *names* select route params and bindings, and whose *types* select the
  request, response, session, auth gate, and form. No container is required.
- **Middleware resolves through the same services.** A middleware class simply
  declares what it needs; built-in and custom guards are wired identically.
- **A self-contained HTTP layer.** `Request`/`Response` implement the contract
  interfaces with plain arrays, so the package runs under any SAPI, in tests,
  and in CLI without adapters.
- **Helpers stay thin.** Each global function is a one-liner over a class and
  is guarded by `function_exists()`, so applications can pre-empt any name.

## Next steps

- [Quick start](quickstart.md) for a worked example.
- [API reference](api-reference.md) for the complete surface.
- [Testing routes](testing.md) for exercising the pipeline in Pest.
