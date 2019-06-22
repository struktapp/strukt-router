<?php

namespace Strukt\Router\Middleware;

use Strukt\Http\Response;
use Strukt\Http\Request;
use Strukt\Http\Exception\NotFoundException;
use Strukt\Http\Exception\UnauthorizedException;
use Strukt\Router\RouteCollection;
use Strukt\Router\Route;
use Strukt\Core\Registry;

class Router implements MiddlewareInterface{

	public function __construct(){

		$this->registry = Registry::getInstance();

		$this->registry->set("route-collection", new RouteCollection());
	}

	public function endpoint($pattern, $route_func, $http_method, $name){

		if(is_string($route_func)){

 			list($class, $method) = explode("@", $route_func);

 			$rClass = new \ReflectionClass($class);
 			$route_func = $rClass->getMethod($method)->getClosure($rClass->newInstance());
 		}

 		$route = new Route($pattern, $route_func, $http_method, $name);

		$this->registry->get("route-collection")->addRoute($route);
	}

	public function __invoke(Request $request, Response $response, callable $next){

		$uri = $request->getRequestUri();

		if(!is_null(parse_url($uri, PHP_URL_QUERY))){

			list($uri, $qs) = explode("?", $uri);
		}

	 	$method = $request->getMethod();

	 	try{
	 		
	 		$route = $this->registry->get("route-collection")->getRoute($method, $uri);

	 		if(!is_null($route)){

	 			if($this->registry->exists("access.permissions")){

	 				$permissions = $this->registry->get("access.permissions");

	 				$routeName = $route->getName();

	 				if(!empty($routeName)){

	 					if(!in_array($routeName, $permissions)){

	 						throw new UnauthorizedException();
	 					}
	 				}
	 			}

		 		$response = $route->setParam("request", $request)->exec();

		 		if(is_string($response)){

		 			$response = new Response($response);
		 		}
		 	}
		 	else{

		 		throw new NotFoundException();
		 	}
	 	}
	 	catch(\Exception $e){

	 		$code = 500;
	 		
	 		if($e->getCode() > 1){

	 			$code = $e->getCode();
	 		}

	 		$response = new Response($e->getMessage(), $code);
	 	}

		return $next($request, $response);
	}
}