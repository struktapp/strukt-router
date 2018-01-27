<?php

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

class RouterTest extends PHPUnit_Framework_TestCase{

	private $router;
	private $servReqMock;
	private $uriMock;

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

		$this->servReqMock = $this->createMock("Psr\Http\Message\ServerRequestInterface");
		$this->uriMock = $this->createMock("Psr\Http\Message\UriInterface");

		$this->router = new \Strukt\Router\Router($this->servReqMock);

		$this->attrBag = array();
	}

	public function execCall($method, $path){

		$this->uriMock->expects($this->any())
            ->method('getPath')
            ->will($this->returnValue($path));

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

        $this->servReqMock->expects($this->any())
			->method('getMethod')
			->will($this->returnValue($method));
	}

	public function testIndexRoute(){

		$this->execCall("GET", "/");

		$this->router->get("/", function(){

			return "Hello World";
		});

		$resp = $this->router->dispatch();

		$this->assertEquals("Hello World", $resp->getBody());
	}

	public function testHelloWorldRoute(){

		$this->execCall("GET", "/hello/Pitsolu");

		$this->router->get("/hello/{to:alpha}", function($to){

			return "Hello $to!";
		});

		$resp = $this->router->dispatch();

		$this->assertEquals("Hello Pitsolu!", $resp->getBody());
	}

	public function testReqRes(){

		$this->execCall("GET", "/test/10");

		$this->router->any("/test/{id:int}", function(RequestInterface $req, ResponseInterface $res){

			$id = (int) $req->getAttribute('id');

		    $res->getBody()->write("You asked for blog entry {$id}.");

		    return $res;
		});

		$resp = $this->router->dispatch();

		$this->assertEquals("You asked for blog entry 10.", (string)$resp->getBody());
	}
}