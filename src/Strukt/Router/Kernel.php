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
	protected $configs;

	public function __construct(RequestInterface $request){

		$this->request = $request;
		$this->permissions = [];
		$this->configs = [];

		env("acl", false);
	}

	public function providers(array $providers){

		foreach($providers as $provider){

 			$closure = \Strukt\Ref::create($provider)->make()->method("register")->getClosure();

 			call_user_func($closure);
		}
	}

	public function middlewares(array $middlewares){

		foreach($middlewares as $middleware)
 			$this->middlewares[] = \Strukt\Ref::create($middleware)->make()->getInstance();
	}

	public function inject(string $name, callable $func){

		reg(sprintf("@inject.%s", $name), new Event($func));		
	}

	/**
	* @param $path uri pattern
	* @param $func callable
	* @param $action HTTP method
	* @param $config permission|token
	*/
	public function add(string $path, callable $func, string $action="GET", string $config = null){

		$name = arr(["path"=>$path, "action"=>$action])->tokenize();
		$name = sprintf("type:route|%s", $name);
		$this->permissions[$name] = [];

		$allows = [];
		if(!is_null($config)){

			if(str($config)->contains("allows:")){

				$allows = token($config)->get("allows");
				if(!is_string($allows))
					$allows = [$allows];
			}

			if(empty($allows))
				$allows[] = $config;

			$this->permissions[$name] = array_merge($this->permissions[$name], $allows);
			$this->configs[$name] = $config;
		}

		event($name, $func);
	}

	public function get(string $path, callable $func, string $config = null){

		$this->add(action: "GET", path:$path, func:$func, config:$config);
	}

	public function post(string $path, callable $func, string $config = null){

		$this->add(action: "POST", path:$path, func:$func, config:$config);
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

			reg("route.current", $match);
			reg("route.configs", $this->configs);

			$method = $this->request->getMethod();
			$name = arr(["path"=>$match, "action"=>$method])->tokenize();
			$name = sprintf("type:route|%s", $name);

			$event = event($name);
			if(is_null($event))
				$response = new MethodNotAllowed;

			if(!$response instanceof HttpErrorInterface){
		
				$runner = new Runner($this->middlewares);
				$response = $runner($this->request, $response);
				$headers = $response->headers->all();

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