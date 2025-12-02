<?php

namespace Strukt\Router\Middleware;

use Strukt\Contract\Http\RequestInterface;
use Strukt\Contract\Http\ResponseInterface;
use Strukt\Contract\UserInterface;
use Strukt\Contract\MiddlewareInterface;
use Strukt\Http\Response\Plain as PlainResponse;

/**
* @Name(auth)
* @Inject(verify)
*/
class Authentication implements MiddlewareInterface{

	private $event;

	public function __construct(){

		//
	}

	/**
	 * @param \Strukt\Contract\Http\RequestInterface $request
	 * @param \Strukt\Contract\Http\ResponseInterface $response
	 * @param callable $next
	 * 
	 * @return \Strukt\Http\Response\Plain
	 */
	public function __invoke(RequestInterface $request, 
								ResponseInterface $response, callable $next):PlainResponse{

		$rconfig = reg("router.config");
		$fn["verify"] = $rconfig->get("verify");
		$fn["session"] = $rconfig->get("session");
		$user = $fn["verify"]($fn["session"]());

		if(notnull($user))
			if(negate($user instanceof UserInterface))
				raise(sprintf("%s must implement %s!", get_class($user), UserInterface::class));

		$request->setUser($user);

		return $next($request, $response);
	}
}