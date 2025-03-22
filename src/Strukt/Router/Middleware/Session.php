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

		$this->event = event("@inject.session");
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

		$request->setSession($this->event->exec());

		return $next($request, $response);
	}
}