<?php

namespace Strukt\Router\Middleware;

use Strukt\Contract\Http\RequestInterface;
use Strukt\Contract\Http\ResponseInterface;
use Strukt\Contract\MiddlewareInterface;

/**
* @Name(authz)
* @Inject(permissions)
*/
class Authorization implements MiddlewareInterface{

	private $event;
	private $permissions;

	public function __construct(){

		env("acl", true);
	}

	public function __invoke(RequestInterface $request, 
								ResponseInterface $response, callable $next){

		return $next($request, $response);
	}
}