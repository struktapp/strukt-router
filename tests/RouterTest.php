<?php

use Strukt\Http\Response\Plain as Response;
use Strukt\Http\Request;

class RouterTest extends PHPUnit\Framework\TestCase{

	public function boot(){

		$request = Request::createFromGlobals();
		$app = new Strukt\Router\Kernel($request);

		// $app->providers([]);
		$app->middlewares(array(
			
			// \Strukt\Router\Middleware\Session::class,
			// \Strukt\Router\Middleware\Authentication::class,
			\Strukt\Router\Middleware\Authorization::class,
		));

		return array($app, $request);
	}

	/**
    * @runInSeparateProcess
    */
	public function testTokens(){

		$configs = "form:user|middlewares:gverify,oauth";

		list($app, $request) = $this->boot();

		$app->add(action:"GET", path:"/user/{id:int}", func:function(Request $request){

			return new Response('Hello world', 200);

		}, config:$configs);

		$app->init();

		// dd(reg("@strukt.permissions"));
		// dd(reg("route.configs"));

		// $this->assertEquals("user", $tokq->get("form"));
		// $this->assertEquals(["gverify","oauth"], $tokq->get("middlewares"));
	}

	/**
    * @runInSeparateProcess
    */
	public function testDefaultRoute(){

		list($app, $request) = $this->boot();

		$app->get(path:"/", func:function(Request $request){

			return new Response('Hello world', 200);
		});

		$result = $app->run();

		$this->assertEquals($result, "Hello world");
	}

	/**
    * @runInSeparateProcess
    */
	public function testRoutePattern(){

		$_SERVER["REQUEST_URI"] = "/yahman/pitsolu";

		list($app, $request) = $this->boot();

		$app->get("/yahman/{name}", function($name, Request $request){

			return new Response(sprintf("Bombo clat rasta %s!", $name), 200);
		});

		$result = $app->run();

		$this->assertEquals($result, "Bombo clat rasta pitsolu!");
	}

	/**
    * @runInSeparateProcess
    */
	public function testRouteClassController(){

		$_SERVER["REQUEST_URI"] = "/check/pItSoLu";

		list($app, $request) = $this->boot();

		$ref = \Strukt\Ref::create(App\Controller\UserController::class)->make();
		$callable = $ref->method("check")->getClosure();

		$app->get("/check/{username:alpha}", $callable);

		$result = $app->run();

		$this->assertEquals($result, "check pItSoLu");
	}

	/**
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 * @expectedExceptionMessage Method Not Allowed!
	 * @expectedException PHPUnit\Framework\Exception
     */
	public function testRouteMethod(){

		// $this->markTestSkipped("Troubled test!");

		$_SERVER["REQUEST_URI"] = "/user/login";
		$_SERVER["REQUEST_METHOD"] = "GET";

		list($app, $request) = $this->boot();

		$request->query->set("username", "pitsolu");
		$request->query->set("password", "p@55w0rd");

		$app->add(action:"POST", path:"/user/login", func:function(Request $request){

			$username = $request->query->get("username");
			$password = $request->query->get("password");

			return new Response(sprintf("username: %s, password: %s", $username, $password));
		});

		$response = $app->run();

		$this->assertEquals($response, "Method Not Allowed!");
	}
}