<?php

namespace Strukt\Router;

use Strukt\Http\Session\Native as Session;
use Strukt\Router\Kernel as Router;
use Strukt\Contract\Http\RequestInterface;
use Strukt\Contract\Http\SessionInterface;
use Strukt\Http\Request;

class QuickStart{

	protected $router;

	public function __construct(array $options = [], RequestInterface $request = null){

		if(is_null($request))
			$request = Request::createFromGlobals();

		$permissions = [];
		if(array_key_exists("permissions", $options))
			$permissions = array_merge($permissions, $options["permissions"]);

		$middlewares = [
			\Strukt\Router\Middleware\Session::class,
			\Strukt\Router\Middleware\Authentication::class,
			\Strukt\Router\Middleware\Authorization::class
		];
		if(array_key_exists("middlewares", $options))
			$middlewares = array_merge($middlewares, $options["middlewares"]);

		$providers = [];
		if(array_key_exists("providers", $options))
			$providers = array_merge($providers, $options["providers"]);

		$this->router = new Router($request);

		$this->router->inject("permissions", function(SessionInterface $session) use($permissions){

			if($session->has("username"))
				$permissions[] = "strukt:auth"; 
			
			return $permissions;
		});

		$this->router->inject("session", function(){

			return new \Strukt\Http\Session\Native;
		});

		$this->router->inject("verify", function(Session $session){

			$user = new \Strukt\User();
			$user->setUsername($session->get("username"));

			return $user;
		});

		$this->router->providers($providers);
		$this->router->middlewares($middlewares);
	}

	public function get(string $path, callable $func, string $config = null){

		$this->router->add(action: "GET", path:$path, func:$func, config:$config);
	}

	public function post(string $path, callable $func, string $config = null){

		$this->router->add(action: "POST", path:$path, func:$func, config:$config);
	}

	public function delete(string $path, callable $func, string $config = null){

		$this->router->add(action: "DELETE", path:$path, func:$func, config:$config);
	}

	public function patch(string $path, callable $func, string $config = null){

		$this->router->add(action: "PATCH", path:$path, func:$func, config:$config);
	}

	public function put(string $path, callable $func, string $config = null){

		$this->router->add(action: "PUT", path:$path, func:$func, config:$config);
	}

	public function any(string $path, callable $func, string $config = null){

		$this->router->add(action: "ANY", path:$path, func:$func, config:$config);
	}

	public function options(string $path, callable $func, string $config = null){

		$this->router->add(action: "OPTIONS", path:$path, func:$func, config:$config);
	}

	public function getRouter(){

		return $this->router;
	}

	public function run(){

		return $this->router->run();
	}
}