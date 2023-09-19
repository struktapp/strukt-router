<?php

namespace Strukt\Router\Middleware;

use Strukt\Contract\Http\RequestInterface;
use Strukt\Contract\Http\ResponseInterface;
use Strukt\Contract\Middleware\MiddlewareInterface;
use Strukt\Http\Error\Forbidden;

/**
* @Name(authz)
* @Inject(permissions)
*/
class Authorization implements MiddlewareInterface{

	private $event;
	private $permissions;

	public function __construct(){

		$this->event = reg("@inject.permissions");
		$this->permissions = reg("@strukt.permissions");
	}

	public function __invoke(RequestInterface $request, 
								ResponseInterface $response, callable $next){

		$permissions = $this->event->exec();

		if(!is_array($permissions))
			throw new \Exception("Middleware\Authorization an array returned!");

		/**
		* Forbid if permssions disallow
		*/
		if(is_array($permissions))
			if(empty(array_intersect($this->permissions, $permissions)))
				return new Forbidden();

		return $next($request, $response);
	}
}