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
<?php

use Strukt\Core\Registry;
use Strukt\Event\Event;
use Strukt\Fs;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;

$loader = require "vendor/autoload.php";
$loader->add('Strukt', "src/");

$registry = Registry::getInstance();
$registry->set("_staticDir", __DIR__."/public/static");

$request = Request::createFromGlobals();

$request = new Request(

    $_GET,
    $_POST,
    array(),
    $_COOKIE,
    $_FILES,
    $_SERVER
);

//Dependency Injection
foreach(["NotFound"=>404, 
            "MethodNotFound"=>405,
            "Forbidden"=>403, 
            "ServerError"=>500,
            "Ok"=>200, 
            "Redirected"=>302,
            "NoContent"=>204] as $msg=>$code)
    $registry->set(sprintf("Response.%s", $msg), new Event(function() use($code){

        $body = "";
        if(in_array($code, array(403,404,405,500)))
            $body = Fs::cat(sprintf("public/errors/%d.html", $code));

        $res = new Response(

            $body,
            $code,
            array('content-type' => 'text/html')
        );

        return $res;
    }));
```

### Entry Point


```php
//index.php

require "bootstrap.php";

$allowed = []; //array("user_del");

$r = new Strukt\Router\Router($allowed, $request);

$r->before(function(Request $req, Response $res) use ($registry){

    $path = $req->getPathInfo();

    if(trim($path) == "/"){

        $res = new RedirectResponse("/hello/friend");

        $res->send();
    }
});

$r->get("/", function(Response $res){

    $res->setContent(Strukt\Fs::cat("public/static/index.html"));

    return $res;
});

$r->get("/hello/{to:alpha}", function($to, Request $req, Response $res){

    $res->setContent("Hello $to");

    return $res;
});

$r->delete("/user/delete/{id:int}", function($id){

    return "user {$id} deleted!";

}, "user_del");

$r->any("/test/{id:int}", function(Request $req, Response $res){

    $id = (int) $req->query->get('id');
    $res->setContent("You asked for blog entry {$id}.");

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