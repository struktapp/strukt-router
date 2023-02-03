<?php

namespace Strukt\Router\Middleware;

use Strukt\Env;
use Strukt\Http\Response\Plain as Response;
use Strukt\Contract\Http\RequestInterface;
use Strukt\Contract\Http\ResponseInterface;
use Strukt\Http\Exception\ServerErrorException;
use Strukt\Contract\Middleware\MiddlewareInterface;
use Strukt\Contract\Middleware\AbstractMiddleware;

/**
* @Name(except)
* @Require(default)
*/
class ExceptionHandler extends AbstractMiddleware implements MiddlewareInterface{

	public function __construct(){

		//
	}

	public function __invoke(RequestInterface $request, 
								ResponseInterface $response, callable $next){

		try {
			
			return $next($request, $response);
		} 
		catch (ServerErrorException $e){

			if (Env::get("is_dev"))
				return new Response($e->getMessage(), $e->getCode());
			
			return new Response('Im sorry, try again later.', $e->getCode());
		}
	}
}