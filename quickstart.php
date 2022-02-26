<?php

require "vendor/autoload.php";

$app = new Strukt\Router\Kernel(Strukt\Http\Request::createFromGlobals());

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

	return new Strukt\Http\Response($request->getSession()->get("username"));
});

$app->map("/logout", function(Strukt\Http\Request $request){

	$request->getSession()->invalidate();

	return new Strukt\Http\Response("User logged out.");
});

exit($app->run()->getContent());