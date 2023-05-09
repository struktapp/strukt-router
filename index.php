<?php

use Strukt\Http\Response\Plain as Response;
use Strukt\Http\Request;
use Strukt\Http\Response\Redirect as RedirectResponse;
use Strukt\Http\Response\Json as JsonResponse;
use Strukt\Http\Session\Native as Session;

use Strukt\Middleware\ExceptionHandler;
use Strukt\Middleware\Authentication; 
use Strukt\Middleware\Authorization;
// use Strukt\Middleware\Asset as AssetMiddleware;
use Strukt\Middleware\Session as SessionMiddleware;
use Strukt\Middleware\Router as RouterMiddleware;
use Strukt\Provider\XRouter as RouterProvider;

use Strukt\Event;
use Strukt\Env;

// use Strukt\Core\Registry;

session_save_path("/tmp");

$loader = require "vendor/autoload.php";
$loader->add('App', __DIR__.'/fixtures/');
$loader->add('Strukt', __DIR__.'/src/');

// Strukt\Http\Exec::withJsonError();

Env::set("root_dir", getcwd());
Env::set("rel_static_dir", "public/static");
Env::set("is_dev", true);

// $registry = Registry::getSingleton();

Strukt\Reg::set("strukt.useJsonError", true);

$app = new Strukt\Router\Kernel(Request::createFromGlobals());
$app->inject("@inject.permissions", function(){

	return array(

		"permissions" => array(

			// "show_secrets"
		)
	);
});

$app->inject("@inject.verify", function(Session $session){

	$user = new Strukt\User();
	$user->setUsername($session->get("username"));

	return $user;
});

$app->inject("@inject.session", function(){

	return new Strukt\Http\Session\Native;
});

$app->providers(array(

	RouterProvider::class
));

$app->middlewares(array(

	ExceptionHandler::class,
	SessionMiddleware::class,
	Authorization::class,
	Authentication::class,
	// AssetMiddleware::class,
	RouterMiddleware::class
));

$app->map("/", function(){

	return "Strukt works!";
});

$app->map("GET", "/user/secrets", function(){

	return "Shh!";

},"show_secrets", ["@index","@user:1"]);

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

$app->map("/test/htmlfile", function(Request $request){// use($registry){

	// $assets = $registry->get("assets");

	// return new Response($assets->get("/index.html"));

	return new Response(\Strukt\Fs::cat("public/static/index.html"));
});


$app->map("/test/resp", function(Request $request, Response $response){// use($registry){

	// $assets = $registry->get("assets");

	// return new Response($assets->get("/index.html"));

	return $response->setContent("Hi Response!");
});

$app->map("/except", function(){

	// return new Strukt\Http\Error\NotFound();
	// return new Strukt\Http\Error\ServerError();
	return new Strukt\Http\Error\BadRequest;
	// throw new Exception("Error Processing Request", 1);
	
	
	// new \Strukt\Http\Response\Json()
});

$app->map("/startpage", "App\Controller\StartpageController@run");

$app->make()->withHeaders()->exec();
// $response = $app->make()->withHeaders()->run();

// exit($response->getContent());
// echo $response->getContent();