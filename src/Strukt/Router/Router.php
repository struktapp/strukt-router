<?php

namespace Strukt\Router;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

class Router{

	private $routes;
	private $servReq = null;

	public function __construct(ServerRequestInterface $servReq = null){

		$this->servReq = $servReq;
	}

	public function newRoute($method, $url, \Closure $callable){

		$this->routes[$url] = array(

			"action"=>new Route($url, $callable),
			"method"=>$method
		);
	}

	public function get($url, \Closure $callable){

		$this->newRoute("GET", $url, $callable);
	}

	public function post($url, \Closure $callable){

		$this->newRoute("POST", $url, $callable);
	}

	public function any($url, \Closure $callable){

		$this->newRoute("ANY", $url, $callable);
	}

	public function dispatch($path=null, $method="GET"){

		if(!is_null($this->servReq)){

			$path = $this->servReq->getUri()->getPath();
			$method = $this->servReq->getMethod();
		}

		if(in_array($path, array_keys($this->routes))){

			$route = $this->routes[$path];
			$routes[$path] = $route;
		}
		else{

			$routes = $this->routes;
		}

		foreach($routes as $route){

			if($route["method"] == "ANY"){

				if($route["action"]->isMatch($path)){

					$params = $route["action"]->getParams();
					foreach($params as $key=>$param)
						$this->servReq = $this->servReq->withAttribute($key, $param[$key]);

					$route["action"]
						->addParam($this->servReq)
						->addParam(new \Kambo\Http\Message\Response);

					return $route["action"]->exec();
				}
			}
			else{

				if($route["action"]->isMatch($path)){

					if($route["method"] != $method)
						throw new \Exception("Route method {$method} does not exist for {$path}!");

					return $route["action"]->exec();
				}
			}
		}
	}
} 