<?php

class RouteTest extends PHPUnit_Framework_TestCase{

	public function setUp(){

		$this->route = new Strukt\Router\Route("/test/{id:int}", function($id){

			return "This is an ". $id;
		});
	}

	public function testGetParams(){

		$this->route->isMatch("/test/23");

		$expected = array(

			"id"=>23
		);

		$this->assertEquals($expected, $this->route->getParams());
	}

	public function testExec(){

		// $this->route->addParam(1);

		$this->route->isMatch("/test/44");

		$this->assertEquals("This is an 44", $this->route->exec());
	}
}