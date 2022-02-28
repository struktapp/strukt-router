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

	public function __construct(array $options = []){

		$defaults = array(

			"permissions"=>[],
			"providers"=>array(

				RouterProvider::class
			),
			"middlewares"=>array(

				SessionMiddleware::class,
				AuthenticationMiddleware::class,
				AuthorizationMiddleware::class,
				RouterMiddleware::class
			)
		);

		$options = array_merge($defaults, $options);

		$this->router = new Router(Request::createFromGlobals());
		$this->router->inject("app.dep.author", function() use ($options){

			return array(

				"permissions"=>$options["permissions"]
			);
		});

		$this->router->inject("app.dep.session", function(){

			return new Session;
		});

		$this->router->inject("app.dep.authentic", function(Session $session){

			$user = null;

			if($session->has("username")){

				$user = new \Strukt\User();
				$user->setUsername($session->get("username"));
				$user->setToken($session->get("token"));
			}

			return $user;
		});

		$this->router->providers($options["providers"]);
		$this->router->middlewares($options["middlewares"]);
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

	public function run(){

		exit($this->router->run()->getContent());
	}
}