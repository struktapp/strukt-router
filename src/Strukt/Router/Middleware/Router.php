<?php

namespace Strukt\Router\Middleware;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Strukt\Router\RouteCollection;
use Strukt\Router\Route;
use Strukt\Core\Registry;
use Strukt\Router\Exception\NotFoundException;

class Router implements MiddlewareInterface{

	public function __construct(){

		$this->collection = new RouteCollection();

		Registry::getInstance()->set("route-collection", $this->collection);
	}

	public function endpoint($pattern, $route, $http_method){

		if(is_string($route)){

 			list($class, $method) = explode("@", $route);

 			$rClass = new \ReflectionClass($class);
 			$route = $rClass->getMethod($method)->getClosure($rClass->newInstance());
 		}

		$this->collection->addRoute(new Route($pattern, $route, $http_method));
	}

	public function __invoke(Request $request, Response $response, callable $next){

	 	$uri = $request->getRequestUri();
	 	$method = $request->getMethod();

	 	try{
	 		
	 		$route = $this->collection->getRoute($method, $uri);

	 		if(!is_null($route)){

		 		$response = $route->setParam("request", $request)->exec();

		 		if(is_string($response)){

		 			$response = new Response($response);
		 		}
		 	}
		 	else throw new NotFoundException();
	 	}
	 	catch(\Exception $e){

	 		$code = 500;
	 		if($e->getCode() > 1)
	 			$code = $e->getCode();

	 		// print_r($e);

	 		$response = new Response($e->getMessage(), $code);
	 	}

		return $next($request, $response);
	}
}