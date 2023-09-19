<?php

namespace Strukt\Router;

use Strukt\Http\Response\Plain as PlainResponse;
use Strukt\Http\Response\Json as JsonResponse;
use Strukt\Contract\Http\RequestInterface;
use Strukt\Contract\Http\ResponseInterface;
use Strukt\Contract\Http\Error\HttpErrorInterface;
use Strukt\Contract\Middleware\MiddlewareInterface;
use Strukt\Router\UrlMatcher;
use Strukt\Http\Error\MethodNotAllowed;
use Strukt\Http\Error\NotFound;
use Strukt\Http\Error\Unauthorized;
use Strukt\Raise;
use Strukt\Event;

class Kernel{

	protected $middlewares;
	protected $request;
	protected $permissions;

	public function __construct(RequestInterface $request){

		$this->request = $request;
		$this->permissions = [];

		env("acl", false);
	}

	public function middlewares(array $middlewares){

		$this->middlewares = $middlewares;
	}

	public function inject(string $name, callable $func){

		reg(sprintf("@inject.%s", $name), new Event($func));		
	}

	/**
	* @param $path uri pattern
	* @param $func callable
	* @param $action HTTP method
	* @param $allow[] list of permissions
	*/
	public function add(string $path, callable $func, string $action="GET", string $allow = null){

		$name = arr(["path"=>$path, "action"=>$action])->tokenize();

		if(!is_null($allow)){

			if(!empty($this->permissions))
				if(!in_array($allow, $this->permissions))
					new Raise(sprintf("Repeated permission!Failed@[%s:%s]", $path, $allow));

			$this->permissions[$name] = $allow;
		}

		event($name, $func);
	}

	public function get(string $path, callable $func, string $allow = null){

		$this->add(action: "GET", path:$path, func:$func, allow:$allow);
	}

	public function post(string $path, callable $func, string $allow = null){

		$this->add(action: "POST", path:$path, func:$func, allow:$allow);
	}

	public function run(){

		reg("@strukt.permissions", $this->permissions);

		$response = new PlainResponse;
		$uri = $this->request->getRequestUri();
		if(!is_null(parse_url($uri, PHP_URL_QUERY)))
			list($uri, $qs) = explode("?", $uri);

		$matcher = matcher();
		$match = $matcher->which($uri);
		if(is_null($match))
			$response = new NotFound;

		if(!$response instanceof HttpErrorInterface){

			$method = $this->request->getMethod();
			$name = arr(["path"=>$match, "action"=>$method])->tokenize();

			$event = event($name);
			if(is_null($event))
				$response = new MethodNotAllowed;

			if(!$response instanceof HttpErrorInterface){
		
				$runner = new Runner($this->middlewares);
				$response = $runner($this->request, $response);
				$headers = $response->headers->all();

				if(env("acl") && reg("@inject")->exists("permissions")){

					$permissions = reg("@inject.permissions")->exec();

					$permission = $this->permissions[$name];
					if(!empty($permission))
						if(!in_array($permission, $permissions))
							$response = new Unauthorized;
				}

				if(!$response instanceof HttpErrorInterface){

					$params = $matcher->params();
					$request = $this->request;
					$params = arr($event->getParams())->each(function($name, $type) 
													use($request, $response, $params){

						if(class_exists($type)){

							$interface = @end(class_implements($type));
							if($interface == RequestInterface::class)
								return $request;

							if($interface == ResponseInterface::class)
								return $response;
						}

						return $params[$name];
					});
					
					$params = $params->yield();
					if(!empty($params))
						$event = $event->applyArgs($params);

					$response = $event->exec();

					if(is_string($response))
				 		$response = new PlainResponse($response, 200, $headers);

				 	if(is_array($response))
				 		$response = new JsonResponse($response, 200, $headers);
				}
			}
		}

		exit($response->getContent());
	}
}