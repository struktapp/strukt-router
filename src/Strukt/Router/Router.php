<?php

namespace Strukt\Router;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Strukt\Core\Registry;

use  Strukt\Event\Single;

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

	public function addRoute($method, $url, \Closure $callable = null, $group=null){

		$action = null;
		if(get_class($callable) == "Closure")
			$action = new Route($url, $callable);

		$this->routes[$url] = array(

			"action"=>$action,
			"method"=>$method,
			"group"=>$group
		);
	}

	public function before(\Closure $func){

		$event = Single::newEvent($func);

		$event->getEvent()->exec();
	}

	public function get($url, \Closure $callable, $group = null){

		$this->addRoute("GET", $url, $callable, $group);
	}

	public function post($url, \Closure $callable, $group = null){

		$this->addRoute("POST", $url, $callable, $group);
	}

	public function delete($url, \Closure $callable, $group = null){

		$this->addRoute("DELETE", $url, $callable, $group);
	}

	public function any($url, \Closure $callable, $group = null){

		$this->addRoute("ANY", $url, $callable, $group);
	}

	private function validate($result){

		if($result instanceof Single){

			$result = $result->exec();
		}

		if(is_string($result)){

			$response = $this->registry->get("Response.Ok")->exec();

			$response->getBody()->write($result);

			return $response;
		}

		return $result;
	}

	public static function emit(ResponseInterface $response){

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

		// print_r($this->servReq);

		if(in_array($path, array_keys($this->routes))){

			$route = $this->routes[$path];
			$routes[$path] = $route;
		}
		else{

			$routes = $this->routes;
		}

		// print_r(get_class($route["action"]));exit;

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

				if($route["method"] != $method && $route["method"]!="ANY")
					return $this->registry->get("Response.MethodNotFound")->exec();

				$params = $route["action"]->getParams();

				if(!empty($params)){

					foreach($params as $key=>$param)
						@$this->servReq = $this->servReq->withAttribute($key, $params[$key]);
				}



				$properties = $route["action"]->getEvent()->getParams();

				// print_r($route); exit;

				foreach($properties as $property){

					if($property->hasType()){

						if($property->getType() == "Psr\Http\Message\ResponseInterface"){

							$res = $this->registry->get("Response.Ok")->exec();

							$route["action"]->setParam($property->getName(), $res);
						}

						if($property->getType() == "Psr\Http\Message\RequestInterface"){

							$route["action"]->setParam($property->getName(), $this->servReq);
						}
					}
				}

				return $this->validate($route["action"]->exec());
			}
		}

        return $this->registry->get("Response.NotFound")->exec();
	}

	public function run(){

		$this->emit($this->dispatch());
	}
} 