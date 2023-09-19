<?php

use Strukt\Http\Request;

require "vendor/autoload.php";

$app = new Strukt\Router\Kernel(Request::createFromGlobals());
$app->middlewares([

	Strukt\Router\Middleware\Authorization::class,
	Strukt\Router\Middleware\Session::class,
	Strukt\Router\Middleware\Router::class,
]);
$app->inject("session", function(){

	return new Strukt\Http\Session\Native;
});
$app->inject("permissions", function(){

	return [];
});
$app->inject("verify", function(Session $session){

	$user = new Strukt\User();
	$user->setUsername($session->get("username"));

	return $user;
});
$app->get("/", function(){

	return "Hello World!";
});

$app->get("/hello/{name}", function($name, Request $request){

	return sprintf("Hello %s!", $name);
});

$app->post("/login", function(Request $request){

	$username = $request->get("username");
	$password = $request->get("password");

	return sprintf("Username: %s| Password: %s", $username, $password);
});

$app->run();