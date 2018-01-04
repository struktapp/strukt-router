<?php

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

class RouteReqResTest extends PHPUnit_Framework_TestCase{

	public function setUp(){

		$clos = function($id, RequestInterface $req, ResponseInterface $res){

			return "This is an ". $id;
		};

		$this->route = new Strukt\Router\Route("/test/{id:int}", $clos);
	}

	public function testGetParams(){

		$params = $this->route->getEvent()->getParams();

		$this->assertFalse($params[0]->hasType());
		$this->assertEquals("Psr\Http\Message\RequestInterface", (string)$params[1]->getType());
		$this->assertEquals("Psr\Http\Message\ResponseInterface", (string)$params[2]->getType());
	}

	public function testGetParamName(){

		$params = $this->route->getEvent()->getParams();

		$this->assertEquals("id", $params[0]->getName());
		$this->assertEquals("req", $params[1]->getName());
		$this->assertEquals("res", $params[2]->getName());
	}

}