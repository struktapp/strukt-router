<?php

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

class RouteReqResTest extends PHPUnit_Framework_TestCase{

	public function setUp(){

		$this->route = new Strukt\Router\Route("/test/{id:int}", function($id, RequestInterface $req, ResponseInterface $res){

			return sprintf("This is an %s", $id);
		});
	}

	public function testGetParams(){

		$event = $this->route->getEvent();

		$params = $event->getParams();

		$this->assertTrue(empty($params["id"]));
		$this->assertEquals("Psr\Http\Message\RequestInterface", $params["req"]);
		$this->assertEquals("Psr\Http\Message\ResponseInterface", $params["res"]);
	}
}