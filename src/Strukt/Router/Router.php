<?php

namespace Strukt\Router;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Strukt\Core\Registry;

class Router{

	private $routes;
	private $servReq = null;
	private $registry = null;
	private $allowed = null;


	public function __construct(ServerRequestInterface $servReq = null, Array $allowed = null){

		$this->servReq = $servReq;

		$this->registry = Registry::getInstance();

		$this->allowedGroup = $allowed;
	}

	public function route($method, $url, \Closure $callable, $group=null){

		$this->routes[$url] = array(

			"action"=>new Route($url, $callable),
			"method"=>$method,
			"group"=>$group
		);
	}

	public function get($url, \Closure $callable, $group = null){

		$this->route("GET", $url, $callable, $group);
	}

	public function post($url, \Closure $callable, $group = null){

		$this->route("POST", $url, $callable, $group);
	}

	public function delete($url, \Closure $callable, $group = null){

		$this->route("DELETE", $url, $callable, $group);
	}

	public function any($url, \Closure $callable, $group = null){

		$this->route("ANY", $url, $callable, $group);
	}

	private function validate($result){

		if(!$result instanceof ResponseInterface){

			$response = $this->registry->get("Response.Ok")->exec();
			$response->getBody()->write($result);

			return $response;
		}

		return $result;
	}

	private function emit(ResponseInterface $response){

		$http_line = sprintf('HTTP/%s %s %s',
	        $response->getProtocolVersion(),
	        $response->getStatusCode(),
	        $response->getReasonPhrase());
    
    	header($http_line, true, $response->getStatusCode());

        foreach ($response->getHeaders() as $name => $values) {

            foreach ($values as $value) {

                header(sprintf('%s: %s', $name, $value), false);
            }
        }

        exit((string)$response->getBody());
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

			if($route["action"]->isMatch($path)){

				$isForbidden = false;

				if(!is_null($route["group"])){

					if(!is_null($this->allowedGroup))
						if(!in_array($route["group"], $this->allowedGroup)) 
							$isForbidden = true;

					if(empty($this->allowedGroup))
						$isForbidden = true;

					if($isForbidden)
						return $this->registry->get("Response.Forbidden")->exec();
				}

				if($route["method"] == "ANY"){

					$params = $route["action"]->getParams();
					foreach($params as $key=>$param)
						@$this->servReq = $this->servReq->withAttribute($key, $param[$key]);

					$route["action"]
						->addParam($this->servReq)
						->addParam($this->registry->get("Response.Ok")->exec());
				}
				else if($route["method"] != $method)
					return $this->registry->get("Response.MethodNotFound")->exec();

				return $this->validate($route["action"]->exec());
			}
		}

        return $this->registry->get("Response.NotFound")->exec();
	}

	public function run(){

		$this->emit($this->dispatch());
	}
} 