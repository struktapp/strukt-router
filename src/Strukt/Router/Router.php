<?php

namespace Strukt\Router;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;

use Strukt\Core\Registry;
use Strukt\Event\Event;
use Strukt\Fs;

class Router{

	private $routes = null;
	private $servReq = null;
	private $registry = null;
	private $allowed = null;

	public function __construct(Array $allowed = null, Request $servReq = null){		

		MimeTypes::register();

		$this->registry = Registry::getInstance();

		if(is_null($servReq))
			if($this->registry->exists("servReq"))
				$this->servReq = $this->registry->get("servReq");
			else
				$this->servReq = Request::createFromGlobals();
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
			if($type == Response::class)
				$args[$name] = $this->registry->get("Response.Ok")->exec();
			elseif($type == Request::class)
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

		$body = (string)$this->servReq->getContent();

		parse_str($body, $arr);

		if(!empty($arr)){

			foreach($arr as $key=>$val)
				$this->servReq->query->set($key, $val);
		}
	}

	private function validate($result){

		if($result instanceof Event){

			$result = $result->exec();
		}

		if(is_string($result)){

			$response = $this->registry->get("Response.Ok")->exec();

			$response->setContent($result);

			return $response;
		}

		return $result;
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
		        		$res->headers->set('Content-Type', 'text/plain');
						$res->setContent(Fs::cat($path));
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

	public function dispatch($path=null, $rMethod="GET"){

		if(is_null($path))
			$path = $this->servReq->getPathInfo();

		$reqMethod = $this->servReq->server->get('REQUEST_METHOD');

		if(empty(trim($reqMethod)))
			$reqMethod = $rMethod;

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
					$this->servReq->query->set($key, $routeParams[$key]);

			$routerEventParams = $route->getEvent()->getParams();

			foreach($routerEventParams as $name=>$type){					

				if($type == Response::class){

					$res = $this->registry->get("Response.Ok")->exec();

					$route->setParam($name, $res);
				}

				if($type == Request::class){

					$this->translateRequestBodyToAttributes();

					$route->setParam($name, $this->servReq);
				}
			}

			return $this->validate($route->exec());
		}

		return $this->registry->get("Response.NotFound")->exec();
	}

	public function run(){

		$res = $this->dispatch();

		$res->send();
	}
} 