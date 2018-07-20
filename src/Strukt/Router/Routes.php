<?php

namespace Strukt\Router;

class Routes{

	private $routes;
	private $urls;
	private $methods;

	public function __construct(){

		//
	}

	public function addRoute($method, $tpl_url, $callable, $group = null, $name = null){

		$this->methods[$tpl_url] = $method;

		if(is_null($name))
			$name = sha1(rand());

		$this->urls[$tpl_url] = $name;

		$this->routes[$name] = new Route($tpl_url, $callable, $group, $name);
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