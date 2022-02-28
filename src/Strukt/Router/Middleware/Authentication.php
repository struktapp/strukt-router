<?php

namespace Strukt\Router\Middleware;

use Strukt\Contract\ResponseInterface;
use Strukt\Http\Request;
use Strukt\Contract\UserInterface;
use Strukt\Contract\MiddlewareInterface;
use Strukt\Contract\AbstractMiddleware;

class Authentication extends AbstractMiddleware implements MiddlewareInterface{

	private $auth_event;

	public function __construct(){

		$this->auth_event = $this->core()->get("app.dep.authentic");
	}

	public function __invoke(Request $request, ResponseInterface $response, callable $next){

		$user = $this->auth_event->apply($request->getSession())->exec();

		if(!($user instanceof UserInterface) && !is_null($user))
			throw new \Exception(sprintf("%s must implement %s!", get_class($user), UserInterface::class));

		$request->setUser($user);

		return $next($request, $response);
	}
}