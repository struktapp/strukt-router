<?php

namespace Strukt\Router\Middleware;

use Strukt\Http\Response;
use Strukt\Http\Request;
use Strukt\UserInterface;
use Strukt\Event\Event;
use Strukt\Http\Session;

class Authentication implements MiddlewareInterface{

	private $authenticationEvent;

	public function __construct(Event $authenticationEvent){

		$this->authenticationEvent = $authenticationEvent;
	}

	public function __invoke(Request $request, Response $response, callable $next){

		if(!$this->authenticationEvent->expects(Session::class))
			throw new \Exception(sprintf("Middleware authentication event expects %s as parameter!", Session::class));

		$user = $this->authenticationEvent->apply($request->getSession())->exec();

		if(!($user instanceof UserInterface))
			throw new \Exception("% must implement %s!", get_class($user), UserInterface::class);

		$request->setUser($user);

		return $next($request, $response);
	}
}