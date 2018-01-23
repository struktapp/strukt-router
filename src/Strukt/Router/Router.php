<?php

namespace Strukt\Router;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

use Strukt\Core\Registry;
use Strukt\Event\Event;
use Strukt\Fs;

class Router{

	private $routes;
	private $servReq = null;
	private $registry = null;
	private $allowed = null;


	public function __construct(ServerRequestInterface $servReq = null, Array $allowed = null){

		$this->servReq = $servReq;

		$this->registry = Registry::getInstance();

		$this->allowedGroup = $allowed;

		$this->loadStaticFiles();
	}

	public function addRoute($method, $url, \Closure $callable = null, $group=null){

		$route = null;
		if(get_class($callable) == "Closure")
			$route = new Route($url, $callable);

		$this->routes[$url] = array(

			"route"=>$route,
			"method"=>$method,
			"group"=>$group
		);
	}

	public function before(\Closure $func){

		$sRes = "Psr\Http\Message\ResponseInterface";
		$sReq = "Psr\Http\Message\RequestInterface";

		$event = Event::newEvent($func);

		$params = $event->getParams();
		
		$res = $this->registry->get("Response.Ok")->exec();

		$args = [];
		foreach($params as $name=>$type)
			if($type == $sRes)
				$args[$name] = $res;
			elseif($type == $sReq)
				$args[$name] = $this->servReq;

		if(empty($args))
			throw new \Exception(sprintf("Router::before requires Psr\Http\Message\[%s & %s]!", $sRes, $sReq));

		$event->applyArgs($args)->exec();
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

			        $this->get($shortUrl, function(ResponseInterface $res) use($path){

			        	$ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));

			        	$mimeTypes = array(

							"png"=>"image/png",
							"gif"=>"image/gif",
							"jpeg"=>"image/jpeg",
							"jpg"=>"image/jpeg",
							"swf"=>"application/x-shockwave-flash",
							"swc"=>"application/x-shockwave-flash",
							"psd"=>"image/psd",
							"bmp"=>"image/bmp",
							"tiff"=>"image/tiff",
							"css"=>"text/css",
							"js"=>"text/js",
							"text"=>"text/plain",
							"json"=>"application/json",
							"html"=>"text/html"
						);

			        	if(in_array($ext, array_keys($mimeTypes)))
							$res = $res->withHeader("content-type", $mimeTypes[$ext]);

			        	$res->getBody()->write(Fs::cat($path));

			        	return $res;
			        });
			    }
			}
		}
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

			$event = $route["route"];
			$_method = $route["method"];

			if($event->isMatch($path)){

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

				if($_method != $method && $_method!="ANY")
					return $this->registry->get("Response.MethodNotFound")->exec();

				$rParams = $event->getParams();

				if(!empty($rParams)){

					foreach($rParams as $key=>$rParam)
						@$this->servReq = $this->servReq->withAttribute($key, $rParams[$key]);
				}

				$eParams = $event->getEvent()->getParams();

				foreach($eParams as $name=>$type){					

					if($type == "Psr\Http\Message\ResponseInterface"){

						$res = $this->registry->get("Response.Ok")->exec();

						$event->setParam($name, $res);
					}

					if($type == "Psr\Http\Message\RequestInterface"){

						$event->setParam($name, $this->servReq);
					}
				}

				return $this->validate($event->exec());
			}
		}

        return $this->registry->get("Response.NotFound")->exec();
	}

	public function run(){

		$this->emit($this->dispatch());
	}
} 