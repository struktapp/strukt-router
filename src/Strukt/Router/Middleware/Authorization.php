<?php

namespace Strukt\Router\Middleware;

use Strukt\Contract\ResponseInterface;
use Strukt\Http\Request;
use Strukt\Contract\MiddlewareInterface;
use Strukt\Contract\AbstractMiddleware;

class Authorization extends AbstractMiddleware implements MiddlewareInterface{

	private $event;

	public function __construct(){

		$this->event = $this->core()->get("@inject.permissions");
	}

	public function __invoke(Request $request, ResponseInterface $response, callable $next){

		$permissions = $this->event->apply($request->getSession())->exec();

		if(!is_array($permissions))
			throw new \Exception("Authorization event expects an array!");

		$core = $this->core();
		if(!$core->exists("@strukt"))
			$this->core()->set("@strukt", $permissions);

		return $next($request, $response);
	}
}