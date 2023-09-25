<?php

namespace Strukt\Router\Middleware;

use Strukt\Contract\Http\RequestInterface;
use Strukt\Contract\Http\ResponseInterface;
use Strukt\Contract\MiddlewareInterface;
use Strukt\Http\Error\Unauthorized;
use Strukt\Contract\Http\SessionInterface;

/**
* @Name(authz)
* @Inject(permissions)
*/
class Authorization implements MiddlewareInterface{

	private $event;
	private $permissions;

	public function __construct(){

		$this->permissions = [];
	}

	public function __invoke(RequestInterface $request, 
								ResponseInterface $response, callable $next){

		$match = reg("route.current");
		$method = $request->getMethod();
		$name = arr(["path"=>$match, "action"=>$method])->tokenize();
		$name = sprintf("type:route|%s", $name);

		$this->permissions = reg("@strukt.permissions");

		if(reg("@inject")->exists("permissions")){

			$permissions_event = reg("@inject.permissions");
			$params = $permissions_event->getParams();
			if(arr($params)->has(SessionInterface::class))
				$permissions_event = $permissions_event->apply($request->getSession());

			$permissions = $permissions_event->exec();

			$allows = $this->permissions->get($name);
			if(!empty($allows))
				if(empty(array_intersect($allows, $permissions)))
					return new Unauthorized;
		}

		return $next($request, $response);
	}
}