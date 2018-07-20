<?php

namespace Strukt\Router;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

use Strukt\Core\Registry;
use Strukt\Event\Event;
use Strukt\Fs;

class Router{

	private $routes = null;
	private $servReq = null;
	private $registry = null;
	private $allowed = null;

	public function __construct(ServerRequestInterface $servReq = null, Array $allowed = null){		

		MimeTypes::register();

		$this->registry = Registry::getInstance();

		if(is_null($servReq))
			if($this->registry->exist("servReq"))
				$this->servReq = $this->registry->get("servReq");
			else
				throw new \Exception("Strukt\Router\Router requires servReq object!");
		else
			$this->servReq = $servReq;
				
		$this->allowedGroup = $allowed;

		$this->routes = new Routes();

		$this->loadStaticFiles();
	}

	public function getRoutes(){

		return $this->routes;
	}

	public function before(\Closure $func){

		$event = Event::newEvent($func);

		$params = $event->getParams();

		$args = [];
		foreach($params as $name=>$type)
			if($type == \Psr\Http\Message\ResponseInterface::class)
				$args[$name] = $this->registry->get("Response.Ok")->exec();
			elseif($type == \Psr\Http\Message\RequestInterface::class)
				$args[$name] = $this->servReq;

		if(empty($args))
			throw new \Exception("Router::before requires both Psr\Http\Message\[RequestInterface, ResponseInterface]!");

		$this->translateRequestBodyToAttributes();

		$event->applyArgs($args)->exec();
	}

	public function get($url, \Closure $callable, $group = null, $name = null){

		$this->routes->addRoute("GET", $url, $callable, $group, $name);
	}

	public function post($url, \Closure $callable, $group = null, $name = null){

		$this->routes->addRoute("POST", $url, $callable, $group, $name);
	}

	public function delete($url, \Closure $callable, $group = null, $name = null){

		$this->routes->addRoute("DELETE", $url, $callable, $group, $name);
	}

	public function try($method, $url, \Closure $callable, $group = null, $name = null){

		$this->routes->addRoute($method, $url, $callable, $group, $name);
	}

	private function translateRequestBodyToAttributes(){

		$body = (string)$this->servReq->getParsedBody();

		if(!empty($body)){

			$params = json_decode($body, 1);

			foreach($params as $key=>$val)
				@$this->servReq = $this->servReq->withAttribute($key, $val);
		}
	}

	private function validate($result){

		if($result instanceof Event){

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

	/** 
	* Handle static files
	*
	* @todo load static files to cache for effeciency
	*/
	private function loadStaticFiles(){

		$staticDir = $this->registry->get("_staticDir");

		$staticDir = str_replace("\\", "/", $staticDir);

		if(!empty($staticDir)){

			$dItr = new \RecursiveDirectoryIterator($staticDir);
			$rItrItr  = new \RecursiveIteratorIterator($dItr, \RecursiveIteratorIterator::SELF_FIRST);

			foreach ($rItrItr as $file) {

			    $path = $file->getRealPath();

			    if ($file->isFile()){

			    	$path = str_replace("\\", "/", $path);
			    	$shortUrl = str_replace($staticDir, "", $path);

			    	$mimeTypes = $this->registry->get("mimeTypes");

			    	$ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));

		        	if(in_array($ext, array_keys($mimeTypes))){

		        		$res = $this->registry->get("Response.Ok")->exec();
						$res = $res->withHeader("content-type", $mimeTypes[$ext]);
						$res->getBody()->write(Fs::cat($path));
		        	}
		        	else{

		        		$res = $this->registry->get("Response.Forbidden")->exec();
		        	}

			        $this->get($shortUrl, function() use($res, $mimeTypes){

			        	return $res;
			        });
			    }
			}
		}
	}

	public function dispatch($path=null, $reqMethod="GET"){

		$path = $this->servReq->getUri()->getPath();
		$reqMethod = $this->servReq->getMethod();

		$route = $this->routes->getRouteByUrl($path);

		if(is_null($route))
			$route = $this->routes->matchRouteByPath($path);

		if(!is_null($route)){

			$props = $route->getProperties();

			if(!empty($props["group"])){

				$isForbidden = false;
				if(!is_null($this->allowedGroup))
					if(!in_array($props["group"], $this->allowedGroup))
						$isForbidden = true;

				if(empty($this->allowedGroup))
					$isForbidden = true;

				if($isForbidden)
					return $this->registry->get("Response.Forbidden")->exec();
			}

			$method = $this->routes->getMethodByUrl($props["tpl_url"]);
			if($method != $reqMethod)
				return $this->registry->get("Response.MethodNotFound")->exec();

			$routeParams = $route->getParams();

			if(!empty($routeParams))
				foreach($routeParams as $key=>$rParam)
					@$this->servReq = $this->servReq->withAttribute($key, $routeParams[$key]);

			$routerEventParams = $route->getEvent()->getParams();

			foreach($routerEventParams as $name=>$type){					

				if($type == \Psr\Http\Message\ResponseInterface::class){

					$res = $this->registry->get("Response.Ok")->exec();

					$route->setParam($name, $res);
				}

				if($type == \Psr\Http\Message\RequestInterface::class){

					$this->translateRequestBodyToAttributes();

					$route->setParam($name, $this->servReq);
				}
			}

			return $this->validate($route->exec());
		}

		return $this->registry->get("Response.NotFound")->exec();
	}

	public function run(){

		$this->emit($this->dispatch());
	}
} 