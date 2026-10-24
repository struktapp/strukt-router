# The HTTP layer

The package ships its own HTTP layer under `Strukt\Http`, implementing the
`strukt4/contract` interfaces. It depends on no framework and no PSR package:
requests are built from plain arrays, which makes them equally usable under a
web server, in a test, and in a CLI process.

- [Request](#request)
- [Building requests by hand](#building-requests-by-hand)
- [Response](#response)
- [ResponseBuilder](#responsebuilder)
- [Download](#download)
- [UploadedFile](#uploadedfile)
- [Implementing your own](#implementing-your-own)

## Request

```php
new Request(
    method: 'GET',
    uri: '/search?q=strukt',
    query: [],                 // parsed from the URI when empty
    body: [],                  // e.g. $_POST
    headers: [],
    files: [],                 // e.g. $_FILES
    content: '',               // the raw body
);
```

| Method | Returns | Notes |
| --- | --- | --- |
| `method()` | `string` | uppercased |
| `uri()` | `string` | full target including the query string |
| `path()` | `string` | path only — this is what the router matches on |
| `query($key = null, $default = null)` | `mixed` | query-string values; `null` key returns all |
| `header($name = null, $default = null)` | `string\|array\|null` | **case-insensitive**; `null` name returns all |
| `hasHeader($name)` | `bool` | case-insensitive presence |
| `body()` | `string` | the **raw** body bytes |
| `input($key = null, $default = null)` | `mixed` | parsed value; **body wins over query** |
| `all()` | `array` | query merged with body |
| `isMethod(...$methods)` | `bool` | case-insensitive |
| `file($name)` | `?UploadedFileInterface` | from the `files` map |
| `user()` / `setUser()` | `?UserInterface` | bound by the auth middleware or the kernel |
| `session()` / `setSession()` | `?SessionInterface` | concrete class only; attached by `sess` middleware |

`Request::createFromGlobals()` captures the live PHP request: `$_SERVER` for
the method, URI, and `HTTP_*` headers; `$_GET`/`$_POST` for parameters;
`$_FILES` for uploads; and `php://input` for the raw body.

If the constructor is given an empty `$query` map but the URI carries a query
string, the URI's query is parsed — so `new Request('GET', '/x?page=2')` and
`new Request('GET', '/x', ['page' => 2])` behave the same.

## Building requests by hand

```php
// GET with query
new Request('GET', '/users?page=2');

// POST with a form body — body values win in input()
new Request('POST', '/login', [], ['email' => 'ada@example.com']);

// with headers, e.g. for an API-style test
new Request('GET', '/api/me', [], [], ['Authorization' => 'Bearer t']);
```

The `request()` helper does the same, defaulting to the globals for whatever
you do not pass:

```php
request(['q' => 'strukt'], 'GET', '/search?q=strukt');
```

## Response

```php
$response = new Response();
$response
    ->setStatus(201)                       // 100–599, else InvalidArgumentException
    ->setHeader('Content-Type', 'text/plain')
    ->addHeader('Set-Cookie', 'a=1')       // appends; setHeader replaces
    ->setBody('made');
```

| Method | Notes |
| --- | --- |
| `status()` / `setStatus()` | status code, validated to 100–599 |
| `setHeader()` / `addHeader()` | replace vs append; names are lowercased internally |
| `hasHeader()` / `header()` | case-insensitive; `header()` joins multiple values with `, ` |
| `removeHeader()` | drops a name entirely |
| `setBody()` / `body()` | the payload bytes |
| `setJson()` | encodes with `JSON_THROW_ON_ERROR` and sets the content type |
| `send()` | emits status, headers, and body — guarded by `headers_sent()` |

Headers are stored as lists keyed by lowercase name, so repeated names
(`Set-Cookie`) survive.

## ResponseBuilder

`response()` hands out a builder that carries a status and header set until
one of the terminal methods materialises a `Response`:

```php
response(201, ['X-Source' => 'cli'])->json(['id' => 7]);   // 201 + JSON
response()->plain('text');                                  // text/plain
response()->redirect('/home', 301);                         // Location header
response()->headers(['X-A' => '1'])->body('…');             // merged first
```

`body()` and `plain()` add `Content-Type: text/plain; charset=utf-8` only when
no content type has been set; `json()` always sets `application/json`.

## Download

```php
$file = download('/tmp/report.pdf', 'report.pdf');   // helper or new Download(...)

$file->filename();      // 'report.pdf'
$file->mime();          // 'application/pdf' (detected from the extension)
$file->size();          // bytes on disk (0 when missing)
$file->content();       // file contents, read lazily
$file->inline();        // disposition: inline (attachment() is the default)
$file->toHeaderValue(); // 'attachment; filename="report.pdf"'
$file->send();          // its own headers + the contents
```

Mime detection covers `pdf`, `txt`, `json`, `csv`, `xml`, `zip`, `gz`, the
common image types, `html`, `css`, and `js`; anything else becomes
`application/octet-stream`. `setMime()` overrides detection.

Returned from an action, the kernel maps it onto a `Response` — see
[Downloads](responses.md#downloads).

## UploadedFile

Wraps a single `$_FILES` entry:

```php
$upload = $request->file('avatar');

$upload->name();        // original client filename
$upload->size();        // declared size
$upload->mime();        // client-supplied type, or application/octet-stream
$upload->extension();   // lowercased extension
$upload->error();       // the UPLOAD_ERR_* code
$upload->isValid();     // UPLOAD_ERR_OK and the file exists at path()
$upload->path();        // the temporary path
$upload->contents();    // file bytes
$upload->move('/var/uploads/avatar.png');   // relocate, returns $this
```

`$upload === null` means the field was absent — `Request::file()` never
throws for a missing name.

## Session store

The store handed to middleware and actions satisfies
`Strukt\Contract\Http\SessionInterface`. Three concrete stores ship with the
package:

| Store | Backing | Use |
| --- | --- | --- |
| `Strukt\Session` | in-memory array | tests, CLI, and multi-dispatch scripts |
| `Strukt\Http\Session\Native` | `$_SESSION` | web requests; starts the native session and persists values and flashes |
| `Strukt\Http\Session\Cache` | a `CacheDriverInterface` driver | web requests behind several processes or servers; persists values and flashes in memcached, the filesystem, or memory |

Bind one with `Router::session()`. Without a factory the router defaults to an
empty `Strukt\Session`.

```php
use Strukt\Contract\Http\SessionInterface;
use Strukt\Http\Session\Cache;

$router->session(fn (): SessionInterface => new Cache(memc('sess')));
```

`Cache` writes through to the driver on every mutation, so a login survives the
request. A driver is a namespace, so it doubles as the client key: give each
client its own (`memc('sess_' . $sid)`) to keep sessions isolated. Values and
flashes are stored together under one reserved key, which keeps session keys
that contain dots (`user.id`) from being read as cache paths. The store needs
the optional `strukt/cache` package — see
[installation](installation.md#requirements).

## Implementing your own

Anything satisfying the contract interfaces can replace a concrete class. The
router only ever type-hints `Strukt\Contract\…`:

| Contract | Consumed by |
| --- | --- |
| `RequestInterface` | the kernel, middleware, `Invoker` |
| `ResponseInterface` | the kernel, middleware, `Invoker` |
| `MiddlewareInterface` | the pipeline |
| `SessionInterface` | `sess` middleware, actions, `Strukt\Auth` |
| `AuthInterface` | `auth` middleware, actions, the kernel |
| `FormInterface` | `Invoker::resolveForm()` |
| `UploadedFileInterface` | `Request::file()` |
| `UserInterface` | `verify()`, `permissions()`, `$request->user()` |

So a PSR-7 bridge is a matter of writing two adapter classes — no router
changes are needed.

## Next steps

- [Routing](routing.md) for how `path()` is matched.
- [Responses and errors](responses.md) for emitting and error mapping.
- [API reference](api-reference.md) for complete signatures.
