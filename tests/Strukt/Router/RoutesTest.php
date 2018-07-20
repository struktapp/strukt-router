<?php

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

class RoutesTest extends PHPUnit_Framework_TestCase{

	public function setUp(){

		$registry = Strukt\Core\Registry::getInstance();

		foreach(["Ok"=>200,"Redirected"=>302] as $msg=>$code)
			if(!$registry->exists(sprintf("Response.%s", $msg)))
				$registry->set(sprintf("Response.%s", $msg), new Strukt\Event\Event(function() use($code){

					$res = new Zend\Diactoros\Response();
					$res = $res->withStatus($code);

					return $res;
				}));

		foreach(["NotFound"=>404,
				 	"MethodNotFound"=>405,
				 	"Forbidden"=>403,
					"ServerError"=>500] as $msg=>$code)
			if(!$registry->exists(sprintf("Response.%s", $msg)))
				$registry->set(sprintf("Response.%s", $msg), new Strukt\Event\Event(function() use($code){

					$res = new Zend\Diactoros\Response();
					$res = $res->withStatus($code);
					$res->getBody()->write(\Strukt\Fs::cat(sprintf("public/errors/%d.html", $code)));

					return $res;
				}));

		$this->servReqMock = $this->createMock(Psr\Http\Message\ServerRequestInterface::class);
		$this->uriMock = $this->createMock(Psr\Http\Message\UriInterface::class);

		$this->attrBag = array();

		$this->servReqMock
			->expects($this->any())
            ->method('getUri')
            ->will($this->returnValue($this->uriMock));

        $this->servReqMock
			->expects($this->any())
            ->method('withAttribute')
            ->will($this->returnCallback(function($key, $value){

            	$this->attrBag[$key] = $value;

            	return $this->servReqMock;
            }));   

        $this->servReqMock
			->expects($this->any())
            ->method('getAttribute')
            ->will($this->returnCallback(function($key){

            	return $this->attrBag[$key];
            }));   

		$this->router = new Strukt\Router\Router($this->servReqMock, $allowed = array());

		$this->router->get("/", function(ResponseInterface $res){

			$res->getBody()->write("Hello World!");

			return $res;
		});

		$this->router->get("/hello/{to:alpha}", function($to, RequestInterface $req, 
																ResponseInterface $res){

			$res->getBody()->write("Hello $to");

			return $res;

		}, null, "hello_to");

		$this->router->try("POST", "/role/add", function(RequestInterface $req, 
															ResponseInterface $res){

			$name = $req->getAttribute('name');
			$descr = $req->getAttribute('descr');

			$hash = sha1(json_encode(array("name"=>$name, "descr"=>$descr)));

		    $res->getBody()->write($hash);

		    return $res;
		});	
	}

	public function execReq($method, $path, $reqBody = null){

		if(!is_null($reqBody))
			if(!empty($reqParams = json_decode($reqBody)))
				foreach ($reqParams as $key => $val)
					$this->attrBag[$key] = $val;

		$this->uriMock->expects($this->any())
            ->method('getPath')
            ->will($this->returnValue($path));

		$this->servReqMock->expects($this->any())
			->method('getMethod')
			->will($this->returnValue($method));

	}

	public function testIndex(){

		$this->execReq("GET", "/");

		$routes = $this->router->getRoutes();

		$route = $routes->getRouteByUrl("/");

		$route->setParam("res", new Zend\Diactoros\Response());

		$this->assertEquals("Hello World!", (string)$route->exec()->getBody());
	}

	public function testParameterizedRoute(){

		$this->execReq("GET", "/hello/pitsolu");

		$routes = $this->router->getRoutes();

		$route = $routes->getByName("hello_to");

		$path = $this->servReqMock->getUri()->getPath();

		$params = [];
		if($route->isMatch($path))
			$params = $route->getParams();

		$route->setParam("to", $params["to"]);
		$route->setParam("res", new Zend\Diactoros\Response());
		$route->setParam("req", $this->servReqMock);

		$this->assertEquals("Hello pitsolu", (string)$route->exec()->getBody());
	}

	public function testPostReq(){

		$params = json_encode(array("name"=>"admin", "descr"=>"N/A"));

		$this->execReq("POST", "/role/add", $params);

		$routes = $this->router->getRoutes();

		$route = $routes->getRouteByUrl("/role/add");

		$route->setParam("res", new Zend\Diactoros\Response());
		$route->setParam("req", $this->servReqMock);

		$this->assertEquals((string)$route->exec()->getBody(), sha1($params));
	}
}