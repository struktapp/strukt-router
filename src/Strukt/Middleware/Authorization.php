<?php

namespace Strukt\Middleware;

use Strukt\Contract\Http\RequestInterface;
use Strukt\Contract\Http\ResponseInterface;
use Strukt\Contract\Middleware\MiddlewareInterface;
use Strukt\Contract\Middleware\AbstractMiddleware;

/**
* @Name(authz)
* @Inject(permissions)
*/
class Authorization extends AbstractMiddleware implements MiddlewareInterface{

	private $event;

	public function __construct(){

		$this->event = $this->core()->get("@inject.permissions");
	}

	public function __invoke(RequestInterface $request, 
								ResponseInterface $response, callable $next){

		$permissions = $this->event->apply($request->getSession())->exec();

		if(!is_array($permissions))
			throw new \Exception("Authorization event expects an array!");

		$core = $this->core();
		if(!$core->exists("@strukt"))
			$this->core()->set("@strukt", $permissions);

		return $next($request, $response);
	}
}