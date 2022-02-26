<?php

namespace Strukt\Router;

use Strukt\Http\Exception\MethodNotAllowedException;

class RouteCollection{

	public function __construct(){

		$this->routes = [];
	}

	public function getRoutes(){

		$properties = [];

		foreach($this->routes as $pattern=>$route){

			$properties[] = array(

				"pattern"=>$pattern,
				"method"=>$route->getMethod(),
				"permission"=>$route->getName()
			);
		}

		return $properties;
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

			$http_method = $route->getMethod();
			if($http_method != "ANY")
				if($http_method != $method)
					throw new MethodNotAllowedException();

			$params = $parser->getParams();

			if(!empty($params))
				$route->mergeParams($params);

			return $route;
		}

		return null;
	}
}