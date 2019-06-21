Strukt Router
=============

[![Build Status](https://travis-ci.org/pitsolu/strukt-router.svg?branch=master)](https://packagist.org/packages/strukt/router)
[![Latest Stable Version](https://poser.pugx.org/strukt/router/v/stable)](https://packagist.org/packages/strukt/router)
[![Total Downloads](https://poser.pugx.org/strukt/router/downloads)](https://packagist.org/packages/strukt/router)
[![Latest Unstable Version](https://poser.pugx.org/strukt/router/v/unstable)](https://packagist.org/packages/strukt/router)
[![License](https://poser.pugx.org/strukt/router/license)](https://packagist.org/packages/strukt/router)

## Usage

### Composer

Create `composer.json` script with contents below then run `composer update`

```js
{
    "require":{

        "strukt/router":"dev-master"
    },
    "minimum-stability":"dev"
}
```

After installation run  `composer exec static` to get `public\` directory.

```
    public/
    ├── errors
    │   ├── 403.html
    │   ├── 404.html
    │   ├── 405.html
    │   └── 500.html
    └── static
        ├── css
        │   └── style.css
        ├── index.html
        └── js
            └── script.js
```

## Get Started

```php
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Session\Session as CoreSession;

use Strukt\Router\Middleware\ExceptionHandler;
use Strukt\Router\Middleware\Session;
use Strukt\Router\Middleware\StaticFileFinder;
use Strukt\Router\Middleware\Router;

require "vendor/autoload.php";

$app = new Strukt\Router\Kernel(Request::createFromGlobals());
$app->middlewares(array(
	
	"execption" => new ExceptionHandler("dev"),
	"session" => new Session(new CoreSession()),
	"staticfinder" => new StaticFileFinder(getcwd(), "/public/static"),
	"router" => new Router,
));

$app->map("/user", function(Request $request){

    $id = $request->query->get("id");

    return new Response(sprintf("User id[%s].", $id), 200);
});

$app->map("/hello/{to:alpha}", function($to){

    return new Response("Hello $to");
});

$app->map("POST","/login", "App\Controller\UserController@login");

$response = $app->run();

exit($response->getContent());
```
Run with PHP in-built server:

```sh
php -S localhost:8080 index.php
```
### Apache

`.htaccess` file:

```
DirectoryIndex index.php

RewriteEngine On
RewriteCond %{REQUEST_FILENAME} !-f
RewriteRule . index.php [L]
```

Cheers!