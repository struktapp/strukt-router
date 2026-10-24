# Global helpers

`src/helpers.php` is autoloaded through Composer's `files` mapping, so the
helpers exist in every file once the autoloader is loaded.

Each helper:

1. checks `function_exists()` first — an application-defined global of the
   same name wins;
2. registers itself with the Strukt helper registry via `helper_add()`;
3. delegates to a class — never contains logic of its own.

## The helpers

| Helper | Signature | Returns | Purpose |
| --- | --- | --- | --- |
| `router()` | `router(): Router` | `Router` | The shared router instance (one per process, held in the Strukt registry). |
| `request()` | `request(array $input = [], ?string $method = null, ?string $uri = null, ?array $headers = null): Request` | `Request` | Build a request; `method`/`uri` default to the globals (`GET` and `/` outside a request). |
| `response()` | `response(int $code = 200, array $headers = []): ResponseBuilder` | `ResponseBuilder` | Start a fluent response. |
| `route()` | `route(string $path, string $method = 'GET', array $input = [], ?array $headers = null): ResponseInterface` | response | Build a request **and** dispatch it through `router()` in one call. |
| `matcher()` | `matcher(array $patterns = []): UrlMatcher` | `UrlMatcher` | A standalone matcher for reverse generation or manual probing. |
| `redirect()` | `redirect(string $url, int $status = 302): ResponseInterface` | `Response` | A redirect response. |
| `download()` | `download(string $path, ?string $filename = null): Download` | `Download` | A file response. |

## Examples

```php
// One shared router — register routes on it, dispatch from anywhere
$router = router();
$router->get('/hello', fn () => 'hi from helper');
route('/hello', 'GET');                        // dispatches immediately

// Typed responses without imports
route('/json', 'GET');                         // if it returns an array
response(201)->json(['id' => 7]);
redirect('/home', 301);
download('/tmp/report.pdf', 'report.pdf');

// Manual request building
$req = request(['q' => 'strukt'], 'GET', '/search?q=strukt');
$req->input('q');                              // 'strukt'

// Low-level matching
matcher(['/user/{id:int}'])->params('/user/{id:int}', '/user/9');   // ['id' => 9]
matcher([])->uri('/blog/{year:int}', ['year' => 2026]);             // '/blog/2026'
```

## `router()` is a singleton

`router()` stores its instance with `reg('router')`, so every call returns the
same object within one process. That makes it a convenient place to assemble
application routes from several files:

```php
// routes/web.php
router()->get('/', fn () => 'home')->name('home');

// routes/api.php
router()->group('/api', function (Router $router): void {
    $router->get('/ping', fn () => 'pong')->name('api.ping');
});
```

If you prefer explicit instances — for example, several independent routers
in one test run — construct `new Router()` directly; the helper is optional.

## When helpers are unavailable

The helpers register themselves only if `helper_add()` exists, which comes
from `strukt/commons`. Since this package requires `strukt/commons`, the
helpers are present in any normal install. In a context where the helper
registry is deliberately absent, the functions simply do not exist — use the
classes (`new Router()`, `new Request(...)`, `new ResponseBuilder(...)`)
instead.

## Next steps

- [Quick start](quickstart.md) for the class-first workflow.
- [API reference](api-reference.md) for the classes behind each helper.
- [`examples/11-helpers.php`](../examples/11-helpers.php) for a runnable
  demonstration.
