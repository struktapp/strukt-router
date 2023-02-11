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

	public function make(){

		return new class($this->middlewares,
						$this->request,
						$this->response){

			private $middlewares;
			private $request;
			private $response;

			private $send_headers = false;

			public function __construct($middlewares, $request, $response){

				$this->middlewares = $middlewares;
				$this->request = $request;
				$this->response = $response;
			}

			public function withHeaders(){

				$this->send_headers = true;

				return $this;
			}

			public function run():ResponseInterface{

				$runner = new \Strukt\Router\Runner($this->middlewares);
				$response = $runner($this->request, $this->response);

				if($this->send_headers)
					$response->sendHeaders();

				return $response;
			}

			public function exec(){

				$response = $this->run();
				
				exit($response->getContent());
			}
		};
	}
}