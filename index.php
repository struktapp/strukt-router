<?php

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Session\Session as CoreSession;

use Strukt\Router\Middleware\ExceptionHandler;
use Strukt\Router\Middleware\Session;
use Strukt\Router\Middleware\StaticFileFinder;
use Strukt\Router\Middleware\Router;

$loader = require "vendor/autoload.php";
$loader->add('App', __DIR__.'/fixtures/');
$loader->add('Strukt', __DIR__.'/src/');

$app = new Strukt\Router\Kernel(Request::createFromGlobals());
$app->middlewares(array(
	
	"execption" => new ExceptionHandler("dev"),
	"session" => new Session(new CoreSession()),
	"staticfinder" => new StaticFileFinder(getcwd(), "/public/static"),
	"router" => new Router,
));

$app->map("/", function(){

	$content = Strukt\Fs::cat("public/static/index.html");

    return new Response($content, 200, array("Content-Type"=>"text/html"));
});

$app->map("/hello/world", function(Request $request){

	return new Response('Hello world!', 200);

	// return new RedirectResponse("/hello/pitsolu");
});

$app->map("/hello/{to:alpha}", function($to){

    return new Response("Hello $to");
});

$app->map("DELETE", "/user/{id:int}", function($id){

    return "user {$id} deleted!";

}, "user_del");

$app->map("/tryme", function(Request $request){

	// print_r($request->getSession());

	return new Response("You've been tried!", 200);
});

$app->map("/user", function(Request $request){

	$id = $request->query->get("id");

	return new Response(sprintf("User id[%s].", $id), 200);
});

$app->map("POST","/foo", "App\Controller\FooController@run");
$app->map("/start/pgs", "App\Controller\StartpageController@run");
$app->map("/check/{username:alpha}", "App\Controller\UserController@check");
$app->map("POST","/login", "App\Controller\UserController@login");

$response = $app->run();

echo $response->getContent();

