<?php

namespace Strukt\Router\Middleware;

use Strukt\Contract\Http\RequestInterface;
use Strukt\Contract\Http\ResponseInterface;
use Strukt\Contract\MiddlewareInterface;
use Strukt\Http\Error\Unauthorized;
use Strukt\Contract\Http\SessionInterface;
use Strukt\Http\Response\Plain as PlainResponse;

/**
* @Name(authz)
* @Inject(permissions)
*/
class Authorization implements MiddlewareInterface{

	private $event;
	private $permissions;

	public function __construct(){

		$this->permissions = [];
	}

	/**
	 * @param Strukt\Contract\Http\RequestInterface $request
	 * @param Strukt\Contract\Http\ResponseInterface $response
	 * @param callable $next
	 * 
	 * @return \Strukt\Http\Response\Plain
	 */
	public function __invoke(RequestInterface $request, 
								ResponseInterface $response, callable $next):PlainResponse{

		$match = reg("route.current");
		$method = $request->getMethod();
		$name = arr(["path"=>$match, "action"=>$method])->tokenize();
		$name = sprintf("type:route|%s", $name);

		$this->permissions = reg("@strukt.permissions");
		$permissions_event = event("@inject.permissions");

		if(!is_null($permissions_event)){

			$params = $permissions_event->getParams();
			if(arr($params)->has(SessionInterface::class))
				$permissions_event = $permissions_event->apply($request->getSession());

			$permissions = $permissions_event->exec();

			$allows = $this->permissions->get($name);
			if(!empty($allows))
				if(empty(array_intersect($allows, $permissions)))
					return new Unauthorized;
		}

		return $next($request, $response);
	}
}