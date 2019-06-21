<?php

namespace Strukt\Router;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;

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

		$method = "GET";
		$name = "";

		switch (func_num_args()) {

			case 2:
				list($path, $controller) = func_get_args();
			break;
			case 3:
				list($method, $path, $controller) = func_get_args();
			break;
			case 4:
				list($method, $path, $controller, $name) = func_get_args();
			break;
			default:
				throw new \Exception(sprintf("%s::map expects 2, 3 or 4 arguments!", Kernel::class));
			break;
		}

		$this->middlewares["router"]->endpoint(trim($path), $controller, trim($method), trim($name));
	}

	public function run() : Response{

		$runner = new Runner($this->middlewares);
		$response = $runner($this->request, $this->response);
			
		return $response;
	}
}