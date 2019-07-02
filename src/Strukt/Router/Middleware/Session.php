<?php

namespace Strukt\Router\Middleware;

use Strukt\Http\Response;
use Strukt\Http\Request;
use Strukt\Contract\MiddlewareInterface;
use Strukt\Contract\AbstractMiddleware;

class Session extends AbstractMiddleware implements MiddlewareInterface{

	private $session;

	public function __construct(){

		$this->session = $this->core()->get("app.dep.session")->exec();
	}

	public function __invoke(Request $request, Response $response, callable $next){

		$this->session->start();

		$request->setSession($this->session);

		return $next($request, $response);
	}
}