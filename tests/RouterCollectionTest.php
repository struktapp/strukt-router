<?php

use Strukt\Http\Request;
use Strukt\Provider\XRouter as RouterProvider;
use Strukt\Ref;
use Strukt\Core\Registry;

class RouterCollectionTest extends PHPUnit\Framework\TestCase{

	public function setUp():void{

		$this->registry = Registry::getSingleton();
		$this->provider = Ref::create(RouterProvider::class)->make()->getInstance();
		$this->provider->register();
	}

	public function testTokens(){

		$service = $this->registry->get("strukt.service.router");

		$routes = array(

			array(

				"method"=>"POST",
				"path"=>"/login", 
				"func"=>function(Request $req){

					return "Not yet implemented";
				},
				"token"=>"type:form|form:auth|middlewares:gverify,oauth"
			),
			array(

				"method"=>"POST",
				"path"=>"/hello/{name:alpha}", 
				"func"=>function($name, Request $req){

					return sprintf("Hello %s", $name);
				},
				"token"=>"middlewares:gverify"
			),
		);

		foreach($routes as $item)
			$service->apply($item["path"], 
							$item["func"], 
							$item["method"],
							"",
							$item["token"])->exec();

		$router = $this->registry->get("strukt.router");
		$router = $router->withToken("middlewares:gverify");
		$this->assertEquals(count($router->getMatches()), 2);
		$router = $router->withToken("type:form");
		$this->assertEquals(count($router->getMatches()), 1);
	}
}