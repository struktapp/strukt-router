<?php

require "vendor/autoload.php";

use Strukt\Http\Request;

$router = new Strukt\Router\QuickStart();
$router->get("/", function(Request $request){

	return "Hello world!";
});
exit($router->run());