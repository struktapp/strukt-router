<?php

use Strukt\Http\Response;
use Strukt\Http\Request;
use Strukt\Http\RedirectResponse;
use Strukt\Http\JsonResponse;
use Strukt\Http\Session;

use Strukt\Router\Middleware\ExceptionHandler;
use Strukt\Router\Middleware\Authentication; 
use Strukt\Router\Middleware\Authorization;
use Strukt\Middleware\Asset as AssetMiddleware;
use Strukt\Router\Middleware\Session as SessionMiddleware;
use Strukt\Router\Middleware\Router as RouterMiddleware;
use Strukt\Provider\Router as RouterProvider;

use Strukt\Event;
use Strukt\Env;

use Strukt\Core\Registry;

$loader = require "vendor/autoload.php";
$loader->add('App', __DIR__.'/fixtures/');
$loader->add('Strukt', __DIR__.'/src/');

Env::set("root_dir", getcwd());
Env::set("rel_static_dir", "public/static");
Env::set("is_dev", true);

$registry = Registry::getSingleton();

$app = new Strukt\Router\Kernel(Request::createFromGlobals());
$app->inject("app.dep.author", function(){

	return array(

		"permissions" => array(

			// "show_secrets"
		)
	);
});

$app->inject("app.dep.authentic", function(Session $session){

	$user = new Strukt\User();
	$user->setUsername($session->get("username"));

	return $user;
});

$app->inject("app.dep.session", function(){

	return new Session;
});

$app->providers(array(

	RouterProvider::class
));

$app->middlewares(array(

	ExceptionHandler::class,
	SessionMiddleware::class,
	Authorization::class,
	Authentication::class,
	AssetMiddleware::class,
	RouterMiddleware::class
));

$app->map("/", function(){

	return "Strukt works!";
});

$app->map("GET", "/user/secrets", function(){

	return "Shh!";

},"show_secrets");

$app->map("POST", "/login", function(Request $request){

	$username = $request->get("username");
	$password = $request->get("password");

	$request->getSession()->set("username", $username);

	return new Response(sprintf("User %s logged in.", $username));
});

$app->map("/user/check", function(Request $request){

	return new Response(sprintf("User:%s", $request->getUser()->getUsername()), 200);
});

$app->map("/user", function(Request $req){

	$id = $req->query->get("id");

	return new Response(sprintf("id: %s", $id), 200);
});

$app->map("/hello/{name:alpha}", function($name, Request $request){

	return "Hello ${name}!";
});

$app->map("/logout", function(Request $request){

	$request->getSession()->invalidate();

	return new Response("User logged out.");
});

$app->map("/test/json", function(Request $request){

	return new JsonResponse(array("username"=>"pitsolu"));
});

$app->map("/test/htmlfile", function(Request $request) use($registry){

	$assets = $registry->get("assets");

	return new Response($assets->get("/index.html"));
});

$app->map("/startpage", "App\Controller\StartpageController@run");

$response = $app->run();

echo $response->getContent();