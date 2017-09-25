<?php

class RouterTest extends PHPUnit_Framework_TestCase{

	private $router;
	private $servReqMock;
	private $uriMock;

	public function setUp(){

		$this->servReqMock = $this->createMock("Psr\Http\Message\ServerRequestInterface");
		$this->uriMock = $this->createMock("Psr\Http\Message\UriInterface");
		$this->router = new \Strukt\Router\Router($this->servReqMock);
	}

	public function execCall($method, $path){

		$this->uriMock->expects($this->any())
            ->method('getPath')
            ->will($this->returnValue($path));

		$this->servReqMock
			->expects($this->any())
            ->method('getUri')
            ->will($this->returnValue($this->uriMock));        

        $this->servReqMock->expects($this->any())
			->method('getMethod')
			->will($this->returnValue($method));
	}

	public function testIndexRoute(){

		$this->execCall("GET", "/");

		$this->router->get("/", function(){

			return "Hello World";
		});

		$this->assertEquals("Hello World", $this->router->dispatch());
	}

	public function testHelloWorldRoute(){

		$this->execCall("GET", "/hello/Pitsolu");

		$this->router->get("/hello/{to:alpha}", function($to){

			return "Hello $to!";
		});

		$this->assertEquals("Hello Pitsolu!", $this->router->dispatch());
	}
}