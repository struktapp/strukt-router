<?php

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Session\Session as CoreSession;

// use Strukt\Router\Middleware\ExceptionHandler;
// use Strukt\Router\Middleware\Session;
// use Strukt\Router\Middleware\StaticFileFinder;
use Strukt\Router\Middleware\Router;

class RouterTest extends PHPUnit\Framework\TestCase{

	/**
    * @runInSeparateProcess
    */
	public function testDefaultRoute(){

		$this->app = new Strukt\Router\Kernel(Request::createFromGlobals());
		$this->app->middlewares(array(
			
			"router" => new Router
		));

		$this->app->map("/", function(Request $request){

			return new Response('Hello world', 200);
		});

		$response = $this->app->run();

		$this->assertEquals($response->getContent(), "Hello world");
	}

	/**
    * @runInSeparateProcess
    */
	public function testRoutePattern(){

		$_SERVER["REQUEST_URI"] = "/yahman/pitsolu";

		$this->app = new Strukt\Router\Kernel(Request::createFromGlobals());
		$this->app->middlewares(array(
			
			"router" => new Router
		));

		$this->app->map("/yahman/{name}", function($name, Request $request){

			return new Response(sprintf("Bombo clat rasta %s!", $name), 200);
		});

		$response = $this->app->run();

		$this->assertEquals($response->getContent(), "Bombo clat rasta pitsolu!");
	}

	/**
    * @runInSeparateProcess
    */
	public function testRouteClassController(){

		$_SERVER["REQUEST_URI"] = "/check/pItSoLu";

		$this->app = new Strukt\Router\Kernel(Request::createFromGlobals());
		$this->app->middlewares(array(
			
			"router" => new Router
		));

		$this->app->map("/check/{username:alpha}", "App\Controller\UserController@check");

		$response = $this->app->run();

		$this->assertEquals($response->getContent(), "check pItSoLu");
	}

	/**
	 * @runInSeparateProcess
     */
	public function testRouteMethod(){

		$_SERVER["REQUEST_URI"] = "/user/login";
		$_SERVER["REQUEST_METHOD"] = "GET";

		$request = Request::createFromGlobals();
		$request->query->set("username", "pitsolu");
		$request->query->set("password", "p@55w0rd");

		$this->app = new Strukt\Router\Kernel($request);
		$this->app->middlewares(array(
			
			"router" => new Router
		));

		$this->app->map("POST", "/user/login", function(Request $request){

			$username = $request->query->get("username");
			$password = $request->query->get("password");

			return new Response(sprintf("username: %s, password: %s", $username, $password));
		});

		$response = $this->app->run();

		$this->assertEquals($response->getContent(), "Method Not Allowed!");
	}
}