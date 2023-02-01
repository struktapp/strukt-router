<?php

namespace Strukt\Provider;

use Strukt\Router\RouteCollection;
use Strukt\Router\Route;
use Strukt\Event;
use Strukt\Contract\Provider\AbstractProvider;
use Strukt\Contract\Provider\ProviderInterface;
use Strukt\Ref;

class Router extends AbstractProvider implements ProviderInterface{

	public function __construct(){

		$this->core()->set("strukt.router", new RouteCollection());
	}

	public function register(){

		$core = $this->core();

		$core->set("strukt.service.router", new Event(
			
			function($pattern, $route_func, $http_method, $name, $tokens) use($core){

				if(is_string($route_func)){

		 			list($class, $method) = explode("@", $route_func);

		 			$route_func = Ref::create($class)
		 				->make()
		 				->method($method)
		 				->getClosure();
		 		}

		 		$route = new Route($pattern, $route_func, $http_method, $name, $tokens);

				$core->get("strukt.router")->addRoute($route);
			}
		));
	}
}