<?php

namespace Strukt\Router\Middleware;

use Strukt\Contract\Http\RequestInterface;
use Strukt\Contract\Http\ResponseInterface;
use Strukt\Contract\UserInterface;
use Strukt\Contract\MiddlewareInterface;

/**
* @Name(auth)
* @Inject(verify)
*/
class Authentication implements MiddlewareInterface{

	private $event;

	public function __construct(){

		$this->event = event("@inject.verify");
	}

	public function __invoke(RequestInterface $request, 
								ResponseInterface $response, callable $next){

		$user = $this->event->apply($request->getSession())->exec();

		if(!($user instanceof UserInterface) && !is_null($user))
			throw new \Exception(sprintf("%s must implement %s!", 
											get_class($user), 
											UserInterface::class));

		$request->setUser($user);

		return $next($request, $response);
	}
}