<?php

use Strukt\Http\Response\Plain as Response;
use Strukt\Http\Request;
use Strukt\Middleware\Router as RouterMiddleware;

class RouterTest extends PHPUnit\Framework\TestCase{

	public function boot(){

		$request = Request::createFromGlobals();
		$app = new Strukt\Router\Kernel($request);

		$app->providers(array(

			Strukt\Provider\XRouter::class
		));
		$app->middlewares(array(
			
			Strukt\Middleware\Router::class
		));

		return array($app, $request);
	}

	/**
    * @runInSeparateProcess
    */
	public function testTokens(){

		$tokens = "form:user|middlewares:gverify,oauth";

		list($app, $request) = $this->boot();

		$app->map("GET", "/user/{id:int}", function(Request $request){

			return new Response('Hello world', 200);

		}, "user_id", $tokens);

		$registry = Strukt\Core\Registry::getSingleton();
		$router = $registry->get("strukt.router");
		$route = $router->getByName("user_id");
		$tokq = $route->getTokenQuery();

		$mdls = $tokq->get("middlewares");

		$this->assertEquals("user", $tokq->get("form"));
		$this->assertEquals(["gverify","oauth"], $tokq->get("middlewares"));
	}

	/**
    * @runInSeparateProcess
    */
	public function testDefaultRoute(){

		list($app, $request) = $this->boot();

		$app->map("/", function(Request $request){

			return new Response('Hello world', 200);
		});

		$response = $app->make()->run();

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

		$response = $app->make()->run();

		$this->assertEquals($response->getContent(), "Bombo clat rasta pitsolu!");
	}

	/**
    * @runInSeparateProcess
    */
	public function testRouteClassController(){

		$_SERVER["REQUEST_URI"] = "/check/pItSoLu";

		list($app, $request) = $this->boot();

		$app->map("/check/{username:alpha}", "App\Controller\UserController@check");

		$response = $app->make()->run();

		$this->assertEquals($response->getContent(), "check pItSoLu");
	}

	/**
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 * @expectedExceptionMessage Method Not Allowed!
	 * @expectedException PHPUnit\Framework\Exception
     */
	public function testRouteMethod(){

		$this->markTestSkipped("Troubled test!");

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

		$response = $app->make()->run();

		// $this->assertEquals($response->getStatusCode(), 405);
	}
}