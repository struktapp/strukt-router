<?php

namespace Strukt\Router;

use Strukt\Http\Session\Native as Session;
use Strukt\Router\Kernel as Router;
use Strukt\Contract\Http\RequestInterface;
use Strukt\Contract\Http\SessionInterface;
use Strukt\Http\Request;
use Strukt\Router\Contract\AbstractKernel;

class QuickStart extends AbstractKernel{

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

	public function getRouter(){

		return $this->router;
	}

	public function run(){

		return $this->router->run();
	}
}