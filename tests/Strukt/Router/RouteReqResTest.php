<?php

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;

use PHPUnit\Framework\TestCase;

class RouteReqResTest extends TestCase{

	public function setUp(){

		$this->route = new Strukt\Router\Route("/test/{id:int}", function($id, Request $req, Response $res){

			return sprintf("This is an %s", $id);
		});
	}

	public function testGetParams(){

		$event = $this->route->getEvent();

		$params = $event->getParams();

		$this->assertTrue(empty($params["id"]));
		$this->assertEquals("Symfony\Component\HttpFoundation\Request", $params["req"]);
		$this->assertEquals("Symfony\Component\HttpFoundation\Response", $params["res"]);
	}
}