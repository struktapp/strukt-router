<?php

use Strukt\Http\Request;
use Strukt\Provider\XRouter as RouterProvider;
use Strukt\Ref;
use Strukt\Core\Registry;

class RouterProviderTest extends PHPUnit\Framework\TestCase{

	public function setUp():void{

		$this->registry = Registry::getSingleton();
		$this->provider = Ref::create(RouterProvider::class)->make()->getInstance();
		$this->provider->register();
	}

	public function testProvider(){

		$service = $this->registry->get("strukt.service.router");

		$routes = array(

			array(

				"method"=>"POST",
				"path"=>"/hello/world", 
				"func"=>function(Request $req){

					return "Hello World.";
				}
			),
			array(

				"method"=>"POST",
				"path"=>"/hello/{name:alpha}", 
				"func"=>function($name, Request $req){

					return sprintf("Hello %s", $name);
				}
			),
		);

		foreach($routes as $item)
			$service->apply($item["path"], $item["func"], $item["method"],"","")->exec();

		$route = $this->registry->get("strukt.router")->getRoute("POST", "/hello/pitsolu");
		$params = $route->getEvent()->getParams();

		foreach($params as $name=>$param)
			if($param == Request::class)
				$route->setParam($name, new Request);

		$this->assertEquals($route->exec(), "Hello pitsolu");
	}
}