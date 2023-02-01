<?php

use Strukt\Http\Response\Plain as Response;
use Strukt\Http\Request;
use Strukt\Router\Middleware\Router as RouterMiddleware;

class RouterTest extends PHPUnit\Framework\TestCase{

	public function boot(){

		$request = Request::createFromGlobals();
		$app = new Strukt\Router\Kernel($request);
		// $app->inject("app.dep.author", function(){

		// 	return [];
		// });
		$app->providers(array(

			Strukt\Provider\Router::class
		));
		$app->middlewares(array(
			
			Strukt\Router\Middleware\Router::class
		));

		return array($app, $request);
	}

	/**
    * @runInSeparateProcess
    */
	public function testTokens(){

		$tokens = ["@user", "@view"];

		list($app, $request) = $this->boot();

		$app->map("GET", "/user/{id:int}", function(Request $request){

			return new Response('Hello world', 200);

		}, "user_id", $tokens);

		$registry = Strukt\Core\Registry::getSingleton();
		$router = $registry->get("strukt.router");
		$route = $router->getByName("user_id");
		$route_tokens = $route->getTokens();

		$this->assertEquals($tokens, $route_tokens);
	}

	/**
    * @runInSeparateProcess
    */
	public function testDefaultRoute(){

		list($app, $request) = $this->boot();

		$app->map("/", function(Request $request){

			return new Response('Hello world', 200);
		});

		$response = $app->run();

		$this->assertEquals($response->getContent(), "Hello world");
	}

	/**
    * @runInSeparateProcess
    */
	public function testRoutePattern(){

		$_SERVER["REQUEST_URI"] = "/yahman/pitsolu";

		list($app, $request) = $this->boot();

		$app->map("/yahman/{name}", function($name, Request $request){

			return new Response(sprintf("Bombo clat rasta %s!", $name), 200);
		});

		$response = $app->run();

		$this->assertEquals($response->getContent(), "Bombo clat rasta pitsolu!");
	}

	/**
    * @runInSeparateProcess
    */
	public function testRouteClassController(){

		$_SERVER["REQUEST_URI"] = "/check/pItSoLu";

		list($app, $request) = $this->boot();

		$app->map("/check/{username:alpha}", "App\Controller\UserController@check");

		$response = $app->run();

		$this->assertEquals($response->getContent(), "check pItSoLu");
	}

	/**
	 * @runInSeparateProcess
     */
	public function testRouteMethod(){

		$_SERVER["REQUEST_URI"] = "/user/login";
		$_SERVER["REQUEST_METHOD"] = "GET";

		list($app, $request) = $this->boot();

		$request->query->set("username", "pitsolu");
		$request->query->set("password", "p@55w0rd");

		$app->map("POST", "/user/login", function(Request $request){

			$username = $request->query->get("username");
			$password = $request->query->get("password");

			return new Response(sprintf("username: %s, password: %s", $username, $password));
		});

		$response = $app->run();

		$this->assertEquals($response->getContent(), "Method Not Allowed!");
	}
}