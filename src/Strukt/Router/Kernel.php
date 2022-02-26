<?php

namespace Strukt\Router;

use Strukt\Contract\AbstractCore;
use Strukt\Contract\ResponseInterface;
use Strukt\Http\Response;
use Strukt\Http\Request;
use Strukt\Core\Registry;
use Strukt\Event;
use Strukt\Ref;
use Strukt\Raise;

class Kernel extends AbstractCore{

	private $request;
	private $response;
	private $debug;
	private $env;
	private $middlewares;

	public function __construct(Request $request, string $env = null, bool $debug = false){

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
		if(in_array(strtoupper($arg), array("ANY","PUT", "GET", "PATH", "POST", "DELETE"))){

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

		$this->core()->get("app.service.router")
							->apply($path, $controller, $method, $name)
							->exec();
	}

	public function run() : ResponseInterface{

		$runner = new Runner($this->middlewares);
		$response = $runner($this->request, $this->response);
			
		return $response;
	}
}