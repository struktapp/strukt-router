<?php

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