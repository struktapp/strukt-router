<?php

namespace Strukt\Router;

use Strukt\Contract\AbstractCore;
use Strukt\Contract\Http\ResponseInterface;
use Strukt\Contract\Http\RequestInterface;
use Strukt\Http\Response\Plain as Response;
use Strukt\Event;
use Strukt\Ref;
use Strukt\Raise;

class Kernel extends AbstractCore{

	private $request;
	private $response;
	private $debug;
	private $env;
	private $middlewares;

	public function __construct(RequestInterface $request, 
								string $env = null, 
								bool $debug = false){

		$this->request = $request;
		$this->response = new Response();

		$this->debug = $debug;
		$this->env = $env;
	}

	public function inject($key, callable $val){

		$this->core()->set($key, new Event($val));
	}

	public function providers(array $providers){

		foreach($providers as $provider){

 			$closure = Ref::create($provider)->make()->method("register")->getClosure();

 			call_user_func($closure);
		}
	}

	public function middlewares(array $middlewares){

		foreach($middlewares as $middleware)
 			$this->middlewares[] = Ref::create($middleware)->make()->getInstance();
	}

	public function map() {

		$args = func_get_args();

		$arg = current($args);
		if(in_array(strtoupper($arg), array(

			"ANY",
			"PUT", 
			"GET", 
			"PATH", 
			"POST", 
			"DELETE",
			"PATCH",
			"OPTIONS"

		))){

			$method = trim(strtoupper($arg));
		}

		if(isset($method)){

			$path = trim(next($args));	
		}
		else{

			$path = trim(current($args));
			$method = "GET";
		}

		if(isset($path)){

			$controller = next($args);
			if(is_string($controller))
				$controller = trim($controller);
		}
		else{

			new Raise("Router path must be defined for each route!");
		}

		$name = "";
		if(next($args)){

			$name = trim(current($args));
		}

		$tokens = [];
		if(count($args) > 4)
			$tokens = @$args[4];

		// print_r($tokens);

		$this->core()->get("strukt.service.router")
							->apply($path, $controller, $method, $name, $tokens)
							->exec();
	}

	// public function reMake(array $components){

	// 	return new class($components, $this->middlewares){

	// 		private $request;
	// 		private $response;

	// 		private $middlewares;

	// 		public function __construct($components, $middlewares){

	// 			$this->middlewares = $middlewares;

	// 			$this->request = $components["request"];

	// 			if(!array_key_exists("response", $components))
	// 				$components["response"] = new Response;
				
	// 			$this->response = $components["response"];
	// 		}

	// 		public function run(){

	// 			$runner = new Runner($this->middlewares);
	// 			$response = $runner($this->request, $this->response);

	// 			return $response;
	// 		}
	// 	};
	// }

	public function run() : ResponseInterface{

		$runner = new Runner($this->middlewares);
		$response = $runner($this->request, $this->response);
			
		return $response;
	}
}