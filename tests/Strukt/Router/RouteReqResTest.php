<?php

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

class RouteReqResTest extends PHPUnit_Framework_TestCase{

	public function setUp(){

		$this->route = new Strukt\Router\Route("/test/{id:int}", function($id, RequestInterface $req, ResponseInterface $res){

			return "This is an ". $id;
		});
	}

	public function testGetParams(){

		$event = $this->route->getEvent();

		// print_r($event->getParams());

		$params = $event->getParams();

		$this->assertTrue(empty($params["id"]));
		$this->assertEquals("Psr\Http\Message\RequestInterface", $params["req"]);
		$this->assertEquals("Psr\Http\Message\ResponseInterface", $params["res"]);
	}

	// public function testGetParamName(){

	// 	$params = $this->route->getEvent()->getParams();

	// 	$this->assertEquals("id", $params[0]->getName());
	// 	$this->assertEquals("req", $params[1]->getName());
	// 	$this->assertEquals("res", $params[2]->getName());
	// }

}