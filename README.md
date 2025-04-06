Strukt Router
=============

[![Build Status](https://travis-ci.org/pitsolu/strukt-router.svg?branch=master)](https://packagist.org/packages/strukt/router)
[![Latest Stable Version](https://poser.pugx.org/strukt/router/v/stable)](https://packagist.org/packages/strukt/router)
[![Total Downloads](https://poser.pugx.org/strukt/router/downloads)](https://packagist.org/packages/strukt/router)
[![Latest Unstable Version](https://poser.pugx.org/strukt/router/v/unstable)](https://packagist.org/packages/strukt/router)
[![License](https://poser.pugx.org/strukt/router/license)](https://packagist.org/packages/strukt/router)

## Getting Started

### Quick Start 

Create `composer.json` script with contents below then run `composer update`

```js
{
    "require":{

        "strukt/router":"v1.1.5-alpha"
    },
    "minimum-stability":"dev"
}
```

Your `index.php` file.

```php
require "vendor/autoload.php";

use Strukt\Http\Request;
// use Strukt\Http\Response\Plain as Response;

$app = new Strukt\Router\QuickStart();

$app->get("/", function(Request $request){

    // return new Response("Hello World!");
    return "Hello World!";
});

exit($app->run());
```

## Advanced Router (The Nitty Gritty)

### Permissions

```php
$app->inject("permissions", function(){

    return array(

        // "show_secrets"
    );
});

$app->providers(array(

    //App\Provider\ExampleProvider::class
));

$app->middlewares(array(

    Strukt\Router\Middleware\Session::class,
    Strukt\Router\Middleware\Authentication::class,
    Strukt\Router\Middleware\Authorization::class,
));

$app->get("/user/secrets", function(){

    return "Shh!";

},"show_secrets");

exit($app->run());
```

### Authentication

```php
$app->inject("permissions", function(){

    return [];
});

$app->inject("session", function(){

    return new Strukt\Http\Session\Native;
});

$app->inject("verify", function(Strukt\Http\Session\Native $session){

    $user = new Strukt\User();
    $user->setUsername($session->get("username"));

    return $user;
});

$app->providers(array(

    //App\Provider\ExampleProvider::class
));

$app->middlewares(array(

    Strukt\Router\Middleware\Session::class,
    Strukt\Router\Middleware\Authentication::class,
    Strukt\Router\Middleware\Authorization::class,
));

$app->post("/login", function(Strukt\Http\Request $request){

    $username = $request->get("username");
    $password = $request->get("password");

    $request->getSession()->set("username", $username);

    return new Strukt\Http\Response\Plain(sprintf("User %s logged in.", $username));
});

$app->get("/current/user", function(Strukt\Http\Request $request){

    return $request->getSession()->get("username");
});

$app->get("/logout", function(Strukt\Http\Request $request){

    $request->getSession()->invalidate();

    return new Strukt\Http\Response\Plain("User logged out.");
});

exit($app->run());
```

### Environment

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

### Apache

`.htaccess` file:

```
DirectoryIndex index.php

RewriteEngine On
RewriteCond %{REQUEST_FILENAME} !-f
RewriteRule . index.php [L]
```

## BTW, DB Tip...

[Adminer](adminer.org) is a really neat tool! It is a single file dba and can be placed 
under a router easily! Download the adminer.php file and place in root folder.

```php
$app->any("/dba", function(Request $request){

    include "./adminer-x.x.x.php";

    return new Strukt\Http\Response\Plain();
});
```
Cheers!