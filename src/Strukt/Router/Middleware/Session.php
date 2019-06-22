<?php

namespace Strukt\Router\Middleware;

use Strukt\Http\Session as StruktSession;
use Strukt\Http\Response;
use Strukt\Http\Request;

class Session implements MiddlewareInterface{

	private $session;

	public function __construct(StruktSession $session){

		$this->session = $session;
	}

	public function __invoke(Request $request, Response $response, callable $next){

		$this->session->start();

		$request->setSession($this->session);

		return $next($request, $response);
	}
}