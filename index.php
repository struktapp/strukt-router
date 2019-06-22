<?php

use Strukt\Http\Response;
use Strukt\Http\Request;
use Strukt\Http\RedirectResponse;
use Strukt\Http\Session;

use Strukt\Router\Middleware\ExceptionHandler;
use Strukt\Router\Middleware\Session as SessionMiddleware;
use Strukt\Router\Middleware\Authentication; 
use Strukt\Router\Middleware\Authorization;
use Strukt\Router\Middleware\StaticFileFinder;
use Strukt\Router\Middleware\Router;

use Strukt\Event\Event;

$loader = require "vendor/autoload.php";
$loader->add('App', __DIR__.'/fixtures/');
$loader->add('Strukt', __DIR__.'/src/');

$app = new Strukt\Router\Kernel(Request::createFromGlobals());
$app->middlewares(array(
	
	"execption" => new ExceptionHandler("dev"),
	"session" => new SessionMiddleware(new Session()),
	"authorization" => new Authorization(new Event(function(){

		return array(

			"permissions" => array(

				// "show_secrets"
			)
		);
	})),
	"authentication" => new Authentication(new Event(function(Session $session){

		$user = new Strukt\User();
		$user->setUsername($session->get("username"));

		return $user;
	})),
	"staticfinder" => new StaticFileFinder(getcwd(), "/public/static"),
	"router" => new Router,
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

$app->map("/logout", function(Request $request){

	$request->getSession()->invalidate();

	return new Response("User logged out.");
});

$app->map("/startpage", "App\Controller\StartpageController@run");

$response = $app->run();

echo $response->getContent();

