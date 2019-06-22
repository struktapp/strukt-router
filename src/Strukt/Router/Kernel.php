<?php

namespace Strukt\Router;

use Strukt\Http\Response;
use Strukt\Http\Request;

class Kernel{

	public function __construct(Request $request, string $env = null, bool $debug = false){

		$this->request = $request;
		$this->response = new Response();

		$this->debug = $debug;
		$this->env = $env;
	}

	public function middlewares(array $middlewares){

		$this->middlewares = $middlewares;
	}

	public function map() {

		$args = func_get_args();

		$arg = current($args);
		if(in_array(strtoupper($arg), array("PUT", "GET", "PATH", "POST", "DELETE"))){

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

			throw new \Exception("Router path must be defined for each route!");
		}

		$name = "";
		if(next($args)){

			$name = trim(current($args));
		}

		$this->middlewares["router"]->endpoint($path, $controller, $method, $name);
	}

	public function run() : Response{

		$runner = new Runner($this->middlewares);
		$response = $runner($this->request, $this->response);
			
		return $response;
	}
}