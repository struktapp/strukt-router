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

	// return ["admin_only"];
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

	$request->getSession()->set("username", $username);

	return response()->body(sprintf("User %s logged in.", $username));
});
$app->post("/user/current", function(Request $request){

	return response()->body(sprintf("User:%s", $request->getUser()->getUsername()));
});
$app->post("/logout", function(Request $request){

	$request->getSession()->invalidate();

	return rsponse()->body("User logged out.");
});
$app->get("/secret", function(Request $request){

	return "secret accessed!";
},"admin_only");

$app->run();