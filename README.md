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

        "strukt/router":"dev-master"
    },
    "minimum-stability":"dev"
}
```

Your `index.php` file.

```php
require "vendor/autoload.php";

$app = new Strukt\Router\Kernel(Strukt\Http\Request::createFromGlobals());

$app->providers(array(

    Strukt\Provider\Router::class
));

$app->middlewares(array(

    Strukt\Router\Middleware\Router::class
));

$app->map("/", function(){

    return "Strukt Works!";
});

$app->map("/hello/{something:alpha}", function($something){

    return sprintf("Hello %s!", $something);
});

exit($app->run()->getContent());
```

## Permissions

```php
$app->inject("app.dep.author", function(){

    return array(

        "permissions" => array(

            //"show_secrets"
        )
    );
});

$app->providers(array(

    Strukt\Provider\Router::class
));

$app->middlewares(array(

    Strukt\Router\Middleware\Authorization::class,
    Strukt\Router\Middleware\Router::class
));

$app->map("GET", "/user/secrets", function(){

    return "Shh!";

},"show_secrets");
```

## Authentication

```php
$app->inject("app.dep.author", function(){

    return [];
});

$app->inject("app.dep.session", function(){

    return new Strukt\Http\Session;
});

$app->inject("app.dep.authentic", function(Strukt\Http\Session $session){

    $user = new Strukt\User();
    $user->setUsername($session->get("username"));

    return $user;
});

$app->providers(array(

    Strukt\Provider\Router::class
));

$app->middlewares(array(

    Strukt\Router\Middleware\Session::class,
    Strukt\Router\Middleware\Authentication::class,
    Strukt\Router\Middleware\Authorization::class,
    Strukt\Router\Middleware\Router::class
));

$app->map("POST", "/login", function(Strukt\Http\Request $request){

    $username = $request->get("username");
    $password = $request->get("password");

    $request->getSession()->set("username", $username);

    return new Strukt\Http\Response(sprintf("User %s logged in.", $username));
});

$app->map("/current/user", function(Strukt\Http\Request $request){

    return $request->getSession()->get("username");
});

$app->map("/logout", function(Strukt\Http\Request $request){

    $request->getSession()->invalidate();

    return new Strukt\Http\Response("User logged out.");
});
```

## Environment

```php
Strukt\Env::set("root_dir", getcwd());
Strukt\Env::set("rel_static_dir", "/public/static");
Strukt\Env::set("is_dev", true);
```

## Exception Handler

You can add exception handler middleware (as the first middleware)

```php
Strukt\Router\Middleware\ExceptionHandler::class
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

## Mapping Classes

```php
$app->map("POST","/login", "App\Controller\UserController@login");

```
## Apache

`.htaccess` file:

```
DirectoryIndex index.php

RewriteEngine On
RewriteCond %{REQUEST_FILENAME} !-f
RewriteRule . index.php [L]
```

## DB Tip...

[Adminer](adminer.org) is a really neat tool! It is a single file dba and can be placed 
under a router easily! Download the adminer.php file and place in root folder.

```
$app->map("ANY", "/dba", function(Request $request){

    include "./adminer-x.x.x.php";

    return new Strukt\Http\Response();
});
```
Cheers!