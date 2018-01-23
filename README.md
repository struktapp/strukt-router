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
    │   ├── 403.html
    │   ├── 404.html
    │   ├── 405.html
    │   └── 500.html
    └── static
        ├── css
        │   └── style.css
        ├── index.html
        └── js
            └── script.js
```

## Get Started

### Bootstrap

```php
//bootstrap.php
use Kambo\Http\Message\Environment\Environment;
use Kambo\Http\Message\Factories\Environment\ServerRequestFactory;
use Kambo\Http\Message\Response;

use Strukt\Core\Registry;
use Strukt\Event\Event;
use Strukt\Fs;

$loader = require "vendor/autoload.php";

$registry = Registry::getInstance();
$registry->set("_staticDir", __DIR__."/public/static");

if(empty($_SERVER["REQUEST_SCHEME"]))
    $_SERVER["REQUEST_SCHEME"] = "http";

$env = new Environment($_SERVER, fopen('php://input', 'w+'), $_POST, $_COOKIE, $_FILES);

$servReq = (new ServerRequestFactory())->create($env);

//Dependency Injection
foreach(["NotFound"=>404, "MethodNotFound"=>405,
            "Forbidden"=>403, "ServerError"=>500,
            "Ok"=>200, "Redirected"=>302] as $msg=>$code)
    $registry->set(sprintf("Response.%s", $msg), new Event(function() use($code){

        $res = new Response($code);

        if(in_array($code, array(403,404,405,500)))
            $res->getBody()->write(Fs::cat(sprintf("public/errors/%d.html", $code)));

        return $res;
    }));
```

### Entry Point

This router uses `kambo/httpmessage` which is PSR-7 compliant.

```php
//index.php

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

require "bootstrap.php";

$allowed = []; //array("user_del");

$r = new Strukt\Router\Router($servReq, $allowed);

$r->before(function(RequestInterface $req, ResponseInterface $res){

    $path = $req->getUri()->getPath();

    if($path == "/"){

        $res = $res->withStatus(200)->withHeader('Location', '/hello/friend');

        Strukt\Router\Router::emit($res);
    }
});

$r->get("/", function(ResponseInterface $res){

    $res->getBody()->write(Strukt\Fs::cat("public/static/index.html"));

    return $res;
});

$r->get("/hello/{to:alpha}", function($to, RequestInterface $req, ResponseInterface $res){

    $res->getBody()->write("Hello $to");

    return $res;
});

$r->delete("/user/delete/{id:int}", function($id){

    return "user {$id} deleted!";

}, "user_del");

$r->any("/test/{id:int}", function(RequestInterface $req, ResponseInterface $res){

    $id = (int) $req->getAttribute('id');
    $res->getBody()->write("You asked for blog entry {$id}.");

    return $res;
});

$r->run();
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