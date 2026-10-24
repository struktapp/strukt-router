# Responses and errors

An action's return value becomes the response. The kernel wraps it, maps
middleware and form failures to status codes, and never lets an exception
escape `handle()`.

- [What an action may return](#what-an-action-may-return)
- [The response builder](#the-response-builder)
- [Redirects](#redirects)
- [Downloads](#downloads)
- [Working with headers and bodies](#working-with-headers-and-bodies)
- [Emitting a response](#emitting-a-response)
- [Error mapping](#error-mapping)

## What an action may return

`Kernel::wrap()` accepts exactly five shapes:

| Return value | Becomes |
| --- | --- |
| `string` | the body as-is; no `Content-Type` is added |
| `array` | a JSON body (`Content-Type: application/json`) |
| `ResponseInterface` | the response as-is |
| `DownloadInterface` | a response carrying the file's mime type, length, and `Content-Disposition` |
| `null` | an empty body |

Anything else throws `ResolutionException`, which the kernel reports as a
**500** with the message `Route actions must return a string, an array, or a
ResponseInterface.` — returning an int, an object, or an unhandled value type
is a programming error, not a client error.

```php
$router->get('/text', fn () => 'plain body');
$router->get('/json', fn () => ['ok' => true]);
$router->get('/none', fn () => null);
$router->get('/full', fn (ResponseInterface $r) => $r->setStatus(201)->setBody('made'));
```

## The response builder

`response()` returns a `ResponseBuilder` carrying a status code and header
set, and materialises one of the concrete responses:

```php
response()                          // 200, no extra headers
response(201)
response(202, ['X-Source' => 'cli'])
```

| Builder method | Produces |
| --- | --- |
| `->body($text)` / `->plain($text)` | a `Response` with the body; sets `Content-Type: text/plain; charset=utf-8` unless one is present |
| `->json($data)` | a `Response` with a JSON body and `Content-Type: application/json` |
| `->redirect($url, $status = 302)` | a `Response` with a `Location` header |
| `->headers([...])` | merges extra headers into the pending response |

```php
$router->get('/payload', fn () => response()->json(['ok' => true]));
$router->get('/created', fn () => response(201)->body('made'));
$router->get('/csv', fn () => response()->plain("a,b,c\n1,2,3"));
```

## Redirects

```php
$router->get('/old', fn () => response()->redirect('/new', 301));
$router->get('/away', fn () => redirect('/home'));            // 302
```

The `redirect()` helper is exactly `response($status)->redirect($url)`.

## Downloads

`download()` builds a `Strukt\Http\Download` — a lazy wrapper around a file
path. Two ways to use it:

```php
// 1. return it from an action; the kernel maps it onto the response
$router->get('/report', fn () => download('/tmp/report.pdf', 'report.pdf'));

// 2. emit it directly, outside the router
download('/tmp/report.pdf', 'report.pdf')->send();
```

Returned through the kernel, the download's metadata becomes response headers:

| Header | Source |
| --- | --- |
| `Content-Type` | the extension of the filename (`pdf`, `csv`, `txt`, …) or an explicit mime |
| `Content-Length` | the file size on disk |
| `Content-Disposition` | `attachment; filename="report.pdf"` (or `inline` after `->inline()`) |

The body is the file contents, read lazily when the response is built:

```php
download($path)->inline();                 // serve inline
download($path)->attachment();             // the default
download($path)->setMime('application/pdf');
```

## Working with headers and bodies

`Response` keeps headers as lists keyed by lowercase name, so one name may
carry several values (`Set-Cookie`):

```php
$response->setHeader('X-Thing', 'a');      // replace
$response->addHeader('Set-Cookie', 'a=1'); // append
$response->hasHeader('x-thing');           // case-insensitive
$response->header('X-Thing');              // 'a'  (values joined with ', ')
$response->removeHeader('X-Thing');

$response->setBody('…');
$response->setJson(['a' => 1]);            // body + Content-Type
$response->setStatus(201);                 // 100–599, else InvalidArgumentException
```

Because middleware and the action share the response object, both sides can
contribute:

```php
$router->get('/mutate', fn (ResponseInterface $response) => $response
    ->setStatus(201)
    ->setHeader('X-Created', 'yes')
    ->setBody('created'));
```

## Emitting a response

```php
$response = $router->kernel()->handle($request);   // nothing sent yet
$response->send();                                  // status, headers, echo body
```

`send()` guards with `headers_sent()` and always echoes the body. When you
dispatch in a loop (a script, a test), use `handle()` and read the response —
only call `send()` when the output really is the HTTP payload.

## Error mapping

Everything the pipeline can throw is turned into a response by
`Kernel::error()`:

| Exception | Status | Extra | Body |
| --- | --- | --- | --- |
| `RouteNotFoundException` | 404 | — | `Not found!` |
| `MethodNotAllowedException` | 405 | `Allow: GET, POST` (union of matching routes) | `Method not allowed!` |
| `AuthorizationException` | 401 guest / 403 user | — | `Authentication required!` / `Unauthorized access!` |
| `ValidationException` | 422 | `Content-Type: application/json` | `{"errors":{…}}` |
| `ResolutionException` | 500 | — | `Unable to resolve parameter [x]…` or `Route actions must return…` |
| any other `Throwable` | 500 | — | the exception message |

Rules applied, in order:

1. The 422 branch is checked first because it needs a JSON body.
2. Otherwise an explicit exception code is honoured when it is a plausible
   status (100–599).
3. Anything else — including `ResolutionException`, whose default code is `0`
   — falls back to 500.

So `handle()` **always** returns a response, and no try/catch is needed
around dispatch.

## Next steps

- [Dependency injection](dependency-injection.md) for what actions receive.
- [Troubleshooting](troubleshooting.md) for decoding a 500 message.
- [`examples/07-responses.php`](../examples/07-responses.php) for a runnable
  demonstration.
