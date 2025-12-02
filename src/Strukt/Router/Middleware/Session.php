<?php

namespace Strukt\Router\Middleware;

use Strukt\Contract\Http\RequestInterface;
use Strukt\Contract\Http\ResponseInterface;
use Strukt\Contract\MiddlewareInterface;
use Strukt\Http\Response\Plain as PlainResponse;

/**
* @Name(sess)
* @Inject(session)
*/
class Session implements MiddlewareInterface{

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

		$fn["session"] = reg("router.config")->get("session");
		$request->setSession($fn["session"]());

		return $next($request, $response);
	}
}