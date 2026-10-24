# The route CLI

`routes` lists every route registered by an application's routes file
as an aligned terminal table. It is installed as `vendor/bin/routes`
alongside the package, so no extra dependency is needed to inspect a route
map.

## Usage

```bash
vendor/bin/routes [routes-file]
```

The binary looks for `./routes.php` in the working directory by default. Pass
a path to use a different file:

```bash
vendor/bin/routes config/routes.php
```

`--help` prints the usage text. Missing files and malformed routes files write
the reason to stderr and exit `1`.

## The routes file contract

The routes file is included and must **return** the `Strukt\Router\Router`
instance it declared its routes on:

```php
<?php

use Strukt\Router\Router;

$router = new Router();

$router->get('/', fn () => 'home')->name('home');
$router->get('/user/{id:int}', fn (int $id) => "user {$id}")->name('user.show')->requiresAuth();

return $router;
```

Registering routes on the `router()` helper without returning anything is not
supported; the binary needs the router back to read its collection.

## Columns

| Column | Shows |
| --- | --- |
| `METHODS` | the methods the route accepts (`GET`, `POST`, …; `any()` lists all seven) |
| `PATTERN` | the registered pattern with its typed placeholders |
| `NAME` | the reverse-generation name, or `-` |
| `AUTH` | `auth` for `requiresAuth()`, `perm: …` for `permissions()`, both joined, or `-` for an open route |

```
METHODS  PATTERN         NAME       AUTH
-------  --------------  ---------  ----
GET      /               home       -
GET      /user/{id:int}  user.show  auth
```

Groups appear as their prefixed patterns (the example above registered
`/admin/report`). Routes are listed in registration order, matching how the
collection matches them.

## Exit codes

| Code | Meaning |
| --- | --- |
| `0` | listed successfully, even when the file declares no routes (`No routes registered.`) |
| `1` | the file is missing, returns something other than a `Router`, or an unknown option was passed |

## Programmatic use

The binary is a thin wrapper over `Strukt\Router\Cli\RouteLister`, which the
same consumers can use directly:

```php
use Strukt\Router\Cli\RouteLister;

$router = RouteLister::load('routes.php');

foreach ((new RouteLister($router))->rows() as $row) {
    echo $row['methods'].' '.$row['pattern']."\n";
}
```

`rows()` returns one array per route (`methods`, `pattern`, `name`, `auth`),
and `render()` builds the aligned table. See the
[API reference](api-reference.md#struktroutercliroutelister) for signatures.