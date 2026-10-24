# Testing routes

Because `Kernel::handle()` takes a request and returns a response — with no
globals, no output, and no thrown exceptions — routes are cheap to test
directly. The package's own suite (Pest) does exactly this.

- [Running the suite](#running-the-suite)
- [The dispatch helper](#the-dispatch-helper)
- [Asserting responses](#asserting-responses)
- [Asserting the failure modes](#asserting-the-failure-modes)
- [Testing middleware](#testing-middleware)
- [Testing forms](#testing-forms)
- [Testing auth](#testing-auth)
- [Fixtures](#fixtures)

## Running the suite

```bash
composer test          # Pest
composer lint          # php -l across src, tests, and examples
```

Tests live in `tests/Unit/`:

| File | Covers |
| --- | --- |
| `RouterTest.php` | verbs, method normalisation, patterns, groups, reverse generation, route options. |
| `KernelTest.php` | dispatch, injection, middleware order, forms, auth gates, response wrapping. |
| `UrlMatcherTest.php` | compilation, casting, optionals, wildcards, reverse generation. |
| `RequestResponseTest.php` | the HTTP layer: query/body precedence, headers, uploads, downloads. |

## The dispatch helper

One small helper keeps every test readable:

```php
use Strukt\Contract\Http\ResponseInterface;
use Strukt\Http\Request;
use Strukt\Router\Router;

function dispatch(Router $router, Request $request): ResponseInterface
{
    return $router->kernel()->handle($request);
}
```

Then a test is three lines:

```php
it('injects a typed route parameter into the action', function () {
    $router = new Router();
    $router->get('/user/{id:int}', fn (int $id) => "user[{$id}]");

    $response = dispatch($router, new Request('GET', '/user/7'));

    expect($response->status())->toBe(200);
    expect($response->body())->toBe('user[7]');
});
```

Always call `handle()` in tests, never `send()` — `send()` echoes, which
would leak into the test output.

## Asserting responses

```php
$response = dispatch($router, new Request('GET', '/json'));

$response->status();                       // 200
$response->body();                         // raw body string
$response->header('Content-Type');         // 'application/json'
$response->header('X-Thing', 'fallback');  // default when absent
```

For JSON bodies, decode explicitly:

```php
expect(json_decode($response->body(), true))->toBe(['name' => 'Ada']);
```

With POST data, pass the body in the constructor (fourth argument):

```php
$response = dispatch($router, new Request('POST', '/login', [], [
    'email' => 'ada@example.com',
    'password' => 'secret1',
]));
```

## Asserting the failure modes

| Situation | Request | Assert |
| --- | --- | --- |
| unknown path | `GET /missing` | `404`, body `Not found!` |
| typed param mismatch | `GET /user/abc` for `/user/{id:int}` | `404` — the route never matched |
| wrong method | `POST /submit` for a `GET` route | `405`, `Allow` header |
| guest on a protected route | `GET /dashboard` | `401`, `Authentication required!` |
| missing permission | `GET /admin` | `403`, `Unauthorized access!` |
| failed validation | `POST /login` with bad input | `422`, JSON `errors` map |
| unresolvable argument | a parameter nothing provides | `500`, message contains `Unable to resolve parameter` |

```php
it('returns 405 with the allowed methods', function () {
    $router = new Router();
    $router->get('/submit', fn () => 'done');

    $response = dispatch($router, new Request('POST', '/submit'));

    expect($response->status())->toBe(405);
    expect($response->header('Allow'))->toBe('GET');
    expect($response->body())->toBe('Method not allowed!');
});
```

## Testing middleware

Give the middleware a static log and assert the sequence. The suite ships
`TraceMiddleware` for this:

```php
use Strukt\Router\Fixtures\TraceMiddleware;

it('runs global middleware before route middleware', function () {
    TraceMiddleware::$log = [];

    $router = new Router();
    $router->middleware(TraceMiddleware::class);
    $router->get('/trace', fn () => 'ok')->middleware(TraceMiddleware::class);

    dispatch($router, new Request('GET', '/trace'));

    expect(TraceMiddleware::$log)->toBe([
        'before-/trace',
        'before-/trace',
        'after-/trace',
        'after-/trace',
    ]);
});
```

For header side effects, assert on the response instead:

```php
expect($response->header('X-Arm'))->toBe('yes');
```

## Testing forms

Submit valid and invalid bodies and branch on the status:

```php
$router->post('/login', fn (FormInterface $form) => 'hi '.$form->value('email'))
    ->form(LoginForm::class);

// valid
$ok = dispatch($router, new Request('POST', '/login', [], [
    'email' => 'ada@example.com', 'password' => 'secret123',
]));
expect($ok->status())->toBe(200);
expect($ok->body())->toBe('hi ada@example.com');

// invalid
$bad = dispatch($router, new Request('POST', '/login', [], [
    'email' => '', 'password' => 'abc',
]));
expect($bad->status())->toBe(422);

$errors = json_decode($bad->body(), true)['errors'];
expect($errors)->toHaveKeys(['email', 'password']);
```

## Testing auth

Capture one session store so state carries across dispatches inside a single
test, and drive the flow the way a browser would:

```php
$store = new Session();
$router = new Router();
$router->session(fn (): SessionInterface => $store);
$router->verify(fn (SessionInterface $s): ?UserInterface => $s->get('email') !== null
    ? new User(email: $s->get('email'))
    : null);

expect(dispatch($router, new Request('GET', '/dashboard'))->status())->toBe(401);

dispatch($router, new Request('POST', '/login', [], [
    'email' => 'ada@example.com', 'password' => 'secret1',
]));

expect(dispatch($router, new Request('GET', '/dashboard'))->status())->toBe(200);
```

Reset between tests (or build a fresh store per test) so examples do not leak
state into each other.

## Fixtures

| Fixture | Purpose |
| --- | --- |
| `Strukt\Router\Fixtures\LoginForm` | `required\|email` + `required\|min_length:6` form for validation tests. |
| `Strukt\Router\Fixtures\TraceMiddleware` | static `$log` capturing `before-`/`after-` markers. |

They are autoloaded through the package's `autoload-dev` PSR-4 mapping
(`Strukt\Router\Fixtures\` → `tests/Fixtures/`), so tests can use them
without any setup. Copy them into your own suite as a starting point.

## Next steps

- [Troubleshooting](troubleshooting.md) when an assertion shows a 500.
- [Responses and errors](responses.md) for the exact status bodies.
