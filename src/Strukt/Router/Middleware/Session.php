<?php

namespace Strukt\Router\Middleware;

use Strukt\Contract\Http\RequestInterface;
use Strukt\Contract\Http\ResponseInterface;
use Strukt\Contract\MiddlewareInterface;

/**
* @Name(sess)
* @Inject(session)
*/
class Session implements MiddlewareInterface{

	private $event;

	public function __construct(){

		$this->event = reg("@inject.session");
	}

	public function __invoke(RequestInterface $request, 
								ResponseInterface $response, callable $next){

		$request->setSession($this->event->exec());

		return $next($request, $response);
	}
}