<?php

namespace Strukt\Router;

use Strukt\Router\Kernel as Router;
use Strukt\Http\Request;
use Strukt\Http\Session;

use Strukt\Provider\Router as RouterProvider;

use Strukt\Router\Middleware\Session as SessionMiddleware;
use Strukt\Router\Middleware\Authentication as AuthenticationMiddleware;
use Strukt\Router\Middleware\Authorization as AuthorizationMiddleware;
use Strukt\Router\Middleware\Router as RouterMiddleware;

class QuickStart{

	protected $router;

	public function __construct(array $options = [], Request $request = null){

		$permissions = array(

			"permissions"=>[]
		);
			
		$providers = array(

			RouterProvider::class
		);

		$middlewares = array(

			SessionMiddleware::class,
			AuthenticationMiddleware::class,
			AuthorizationMiddleware::class,
			RouterMiddleware::class
		);

		if(array_key_exists("middlewares", $options))
			$middlewares = $options["middlewares"];

		if(array_key_exists("providers", $options))
			$providers = $options["providers"];

		if(array_key_exists("permissions", $options))
			$permissions = array_merge($permissions, $options["permissions"]);

		if(is_null($request))
			$request = Request::createFromGlobals();

		$use_session = true;
		if(array_key_exists("session", $options))
			$use_session = $options["session"];

		$this->router = new Router($request);
		$this->router->inject("@inject.permissions", function() use ($permissions){

			return array(

				"permissions"=>$permissions["permissions"]
			);
		});

		if($use_session){

			$this->router->inject("@inject.session", function(){

				return new Session;
			});
		}

		$this->router->inject("@inject.verify", function(Session $session){

			$user = null;

			if($session->has("username")){

				$user = new \Strukt\User();
				$user->setUsername($session->get("username"));
				$user->setToken($session->get("user.token"));
			}

			return $user;
		});

		$this->router->providers($providers);
		$this->router->middlewares($middlewares);
	}

	public function get(string $route, callable $func, string $perm = null){

		$this->router->map("GET", $route, $func, $perm);
	}

	public function post(string $route, callable $func, string $perm = null){

		$this->router->map("POST", $route, $func, $perm);
	}

	public function delete(string $route, callable $func, string $perm = null){

		$this->router->map("DELETE", $route, $func, $perm);
	}

	public function patch(string $route, callable $func, string $perm = null){

		$this->router->map("PATCH", $route, $func, $perm);
	}

	public function put(string $route, callable $func, string $perm = null){

		$this->router->map("PUT", $route, $func, $perm);
	}

	public function any(string $route, callable $func, string $perm = null){

		$this->router->map("ANY", $route, $func, $perm);
	}

	public function getRouter(){

		return $this->router;
	}

	public function run(){

		$response = $this->getRouter()->run();

		exit($response->getContent());
	}
}