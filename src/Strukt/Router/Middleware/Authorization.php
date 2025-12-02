<?php

namespace Strukt\Router\Middleware;

use Strukt\Contract\Http\RequestInterface;
use Strukt\Contract\Http\ResponseInterface;
use Strukt\Contract\MiddlewareInterface;
use Strukt\Http\Error\Unauthorized;
use Strukt\Contract\Http\SessionInterface;
use Strukt\Http\Response\Plain as PlainResponse;

/**
* @Name(authz)
* @Inject(permissions)
*/
class Authorization implements MiddlewareInterface{

	private $event;
	private $permissions;

	public function __construct(){

		//
	}

	/**
	 * @param Strukt\Contract\Http\RequestInterface $request
	 * @param Strukt\Contract\Http\ResponseInterface $response
	 * @param callable $next
	 * 
	 * @return \Strukt\Http\Response\Plain
	 */
	public function __invoke(RequestInterface $request, 
								ResponseInterface $response, callable $next):PlainResponse{

		$allow = config("user.allow");
		$rconfig = reg("router.config");
		$fn["session"] = $rconfig->get("session");
		$fn["permissions"] = $rconfig->get("permissions");
		$permissions = $fn["permissions"]($fn["session"]());

		if(negate(empty($allow)))
			if(empty(array_intersect($allow, $permissions)))
				raise("Unauthorized access!", 401);

		return $next($request, $response);
	}
}