<?php

namespace Strukt\Router\Middleware;

use Strukt\Contract\ResponseInterface;
use Strukt\Http\Request;
use Strukt\Contract\MiddlewareInterface;
use Strukt\Contract\AbstractMiddleware;

class Session extends AbstractMiddleware implements MiddlewareInterface{

	private $session;

	public function __construct(){

		//
	}

	public function __invoke(Request $request, ResponseInterface $response, callable $next){

		$this->session = $this->core()->get("@inject.session")->exec();

		if(!$this->session->isStarted())
			$this->session->start();

		$request->setSession($this->session);

		return $next($request, $response);
	}
}