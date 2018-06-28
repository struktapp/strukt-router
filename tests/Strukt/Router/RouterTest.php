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

	public function execReq($method, $path, $reqBody = null){

		if(!is_null($reqBody))
			if(!empty($reqParams = json_decode($reqBody)))
				foreach ($reqParams as $key => $val)
					$this->attrBag[$key] = $val;

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

		$this->execReq("GET", "/");

		$this->router->get("/", function(){

			return "Hello World";
		});

		$resp = $this->router->dispatch();

		$this->assertEquals("Hello World", $resp->getBody());
	}

	public function testHelloWorldRoute(){

		$this->execReq("GET", "/hello/pitsolu");

		$this->router->get("/hello/{to:alpha}", function($to){

			return sprintf("Hello %s!", ucfirst($to));
		});

		$resp = $this->router->dispatch();

		$this->assertEquals("Hello Pitsolu!", $resp->getBody());
	}

	public function testReqRes(){

		$this->execReq("GET", "/test/10");

		$this->router->any("/test/{id:int}", function(RequestInterface $req, ResponseInterface $res){

			$id = (int) $req->getAttribute('id');

		    $res->getBody()->write("You asked for blog entry {$id}.");

		    return $res;
		});

		$resp = $this->router->dispatch();

		$this->assertEquals("You asked for blog entry 10.", (string)$resp->getBody());
	}

	//only a demonstration of how request would work
	public function testReqParams(){

		$params = json_encode(array("name"=>"admin", "descr"=>"N/A"));

		$this->execReq("POST", "/role/add", $params);

		$this->router->any("/role/add", function(RequestInterface $req, ResponseInterface $res){

			$name = $req->getAttribute('name');
			$descr = $req->getAttribute('descr');

			$hash = sha1(json_encode(array("name"=>$name, "descr"=>$descr)));

		    $res->getBody()->write($hash);

		    return $res;
		});

		$resp = $this->router->dispatch();

		$this->assertEquals($resp->getBody(), sha1($params));
	}
}