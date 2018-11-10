<?php

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;

use PHPUnit\Framework\TestCase;

class RoutesTest extends TestCase{

	public function setUp(){

		$registry = Strukt\Core\Registry::getInstance();

		foreach(["Ok"=>200,"Redirected"=>302] as $msg=>$code)
			if(!$registry->exists(sprintf("Response.%s", $msg)))
				$registry->set(sprintf("Response.%s", $msg), new Strukt\Event\Event(function() use($code){

					$res = new Response();
					$res = $res->setStatusCode($code);

					return $res;
				}));

		foreach(["NotFound"=>404,
				 	"MethodNotFound"=>405,
				 	"Forbidden"=>403,
					"ServerError"=>500] as $msg=>$code)
			if(!$registry->exists(sprintf("Response.%s", $msg)))
				$registry->set(sprintf("Response.%s", $msg), new Strukt\Event\Event(function() use($code){

					$res = new Response();
					$res = $res->setStatusCode($code);
					$res->setContent(\Strukt\Fs::cat(sprintf("public/errors/%d.html", $code)));

					return $res;
				}));

		$this->router = new Strukt\Router\Router($allowed = array());

		$this->router->get("/", function(Response $res){

			$res->setContent("Hello World!");

			return $res;
		});

		$this->router->get("/hello/{to:alpha}", function($to, Request $req, 
																Response $res){

			$res->setContent("Hello $to");

			return $res;

		}, null, "hello_to");

		$this->router->try("POST", "/role/add", function(Request $req, 
															Response $res){

			$name = $req->query->get('name');
			$descr = $req->query->get('descr');

			// print_r(array($name, $descr));

			$hash = sha1(json_encode(array("name"=>$name, "descr"=>$descr)));

		    $res->setContent($hash);

		    return $res;
		});	
	}

	public function testIndex(){

		// $this->execReq("GET", "/");

		$routes = $this->router->getRoutes();

		$route = $routes->getRouteByUrl("/");

		$route->setParam("res", new Response());

		$this->assertEquals("Hello World!", (string)$route->exec()->getContent());
	}

	public function testParameterizedRoute(){

		$routes = $this->router->getRoutes();

		$route = $routes->getByName("hello_to");
		
		$params = $route->getParams();

		$route->setParam("to", 'pitsolu');
		$route->setParam("res", new Response());
		$route->setParam("req", new Request());

		$this->assertEquals("Hello pitsolu", (string)$route->exec()->getContent());
	}

	public function testPostReq(){

		$params = json_encode(array("name"=>"admin", "descr"=>"N/A"));

		$routes = $this->router->getRoutes();

		$route = $routes->getRouteByUrl("/role/add");

		$request = new Request();
		$request->query->set('name', 'admin');
		$request->query->set('descr', 'N/A');

		$route->setParam("res", new Response());
		$route->setParam("req", $request);

		$this->assertEquals((string)$route->exec()->getContent(), sha1($params));
	}
}