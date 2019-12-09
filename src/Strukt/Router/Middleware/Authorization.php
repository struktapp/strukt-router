<?php

namespace Strukt\Router\Middleware;

use Strukt\Contract\ResponseInterface;
use Strukt\Http\Request;
use Strukt\Core\Registry;
use Strukt\Event\Event;
use Strukt\Contract\MiddlewareInterface;
use Strukt\Contract\AbstractMiddleware;

class Authorization extends AbstractMiddleware implements MiddlewareInterface{

	private $auth_event;

	public function __construct(){

		$this->auth_event = $this->core()->get("app.dep.author");
	}

	public function __invoke(Request $request, ResponseInterface $response, callable $next){

		$access = $this->auth_event->apply($request->getSession())->exec();

		if(!is_array($access))
			throw new \Exception("Authorization event expects array object!");

		$this->core()->set("access", $access);

		return $next($request, $response);
	}
}