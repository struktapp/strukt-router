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

	public function __construct(){

		$this->event = reg("@inject.permissions");
	}

	public function __invoke(RequestInterface $request, 
								ResponseInterface $response, callable $next){

		$permissions = $this->event->exec();

		if(!is_array($permissions))
			throw new \Exception("Middleware\Authorization an array returned!");

		/**
		* Forbid if permssions disallow
		*/
		if(!empty($permissions))
			if(empty(array_intersect(reg("@strukt.permissions"), $permissions)))
				return new Forbidden();

		return $next($request, $response);
	}
}