<?php

namespace Strukt\Router;

// use Strukt\Cmd;
use Strukt\Http\Response\Plain as PlainResponse;
use Strukt\Contract\Http\RequestInterface;
use Strukt\Raise;
use Strukt\Event;

class Kernel{

	protected $middlewares;
	protected $request;
	protected $permissions;

	public function __construct(RequestInterface $request){

		$this->request = $request;
		$this->permissions = [];

		// reg()->set("@inject", []);
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

		$runner = new Runner($this->middlewares);
		$response = $runner($this->request, new PlainResponse);

		exit($response->getContent());
	}
}