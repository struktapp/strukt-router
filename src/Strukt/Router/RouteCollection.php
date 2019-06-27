<?php

namespace Strukt\Router;

use Strukt\Http\Exception\MethodNotAllowedException;

class RouteCollection{

	public function __construct(){

		$this->routes = [];
	}

	public function getRouteList(){

		return array_keys($this->routes);
	}

	public function addRoute(Route $route){

		$pattern = $route->getPattern();

		$this->routes[$pattern] = $route;
	}

	public function getRoute($method, $uri){

		$parser = new UrlParser(array_keys($this->routes));

		$pattern = $parser->whichPattern($uri);

		if(!is_null($pattern)){

			$route = $this->routes[$pattern];

			if($route->getMethod() != $method)
				throw new MethodNotAllowedException();

			$params = $parser->getParams();

			if(!empty($params))
				$route->mergeParams($params);

			return $route;
		}

		return null;
	}
}