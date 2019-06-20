<?php

namespace Strukt\Router;

class Routes{

	private $routes;
	private $urls;
	private $methods;

	public function __construct(){

		//
	}

	public function addRoute($method, $pattern, $callable, $name = null){

		$this->methods[$pattern] = $method;

		if(is_null($name))
			$name = sha1(rand());

		$this->urls[$pattern] = $name;

		$this->routes[$name] = new Route($pattern, $callable, $name);
	}

	public function getMethodByUrl($url){

		return $this->methods[$url];
	}

	public function getRouteByUrl($url){

		if(!in_array($url, array_keys($this->urls)))
			return null;

		$name = $this->urls[$url];

		return $this->getByName($name);
	}

	public function getByName($name){

		return $this->routes[$name]; 
	}

	public function matchRouteByPath($path){

		foreach($this->routes as $route)
			if($route->isMatch($path))
				return $route;

		return null;
	}
}