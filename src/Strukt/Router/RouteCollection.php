<?php

namespace Strukt\Router;

use Strukt\Http\Error\MethodNotAllowed;
use Strukt\Http\Exec as HttpExec;

class RouteCollection{

	public function __construct(){

		$this->route_patterns = [];
		$this->route_matches = [];
	}

	public function getRoutes(){

		$properties = [];

		foreach($this->route_patterns as $pattern=>$route){

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

		$this->route_patterns[$pattern] = $route;

		$name = $route->getName();
		if(!empty($name))
			$this->route_names[$name] = $route;
	}

	public function getByName(string $name){

		if(array_key_exists($name, $this->route_names))
			return $this->route_names[$name];

		throw new \Exception(sprintf("Route:[name:%s] does not exist!", $name));
	}

	public function matchToken(string $like){

		foreach($this->route_patterns as $pattern=>$route)
			if($route->isMatch($like))
				$routes[$pattern] = $route;

		$this->route_matches = $routes;

		return $this;
	}

	public function getRoute($method, $uri){

		$routes = $this->route_patterns;

		if(!empty($this->route_matches)){

			$routes = $this->route_matches;
			$this->route_matches = [];
		}

		$parser = new UrlParser(array_keys($routes));

		$pattern = $parser->whichPattern($uri);

		if(!is_null($pattern)){

			$route = $routes[$pattern];

			$http_method = $route->getMethod();
			if($http_method != "ANY")
				if($http_method != $method)
					throw new \Exception("Method Not Allowed!");
					// HttpExec::make(new MethodNotAllowed)->withHeaders()->run();
					// throw new MethodNotAllowedException();

			$params = $parser->getParams();
			if(!empty($params))
				$route->mergeParams($params);

			return $route;
		}

		return null;
	}
}