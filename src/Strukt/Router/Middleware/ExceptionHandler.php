<?php

namespace Strukt\Router\Middleware;

use Strukt\Env;
use Strukt\Http\Response\Plain as Response;
use Strukt\Contract\RequestInterface;
use Strukt\Contract\ResponseInterface;
use Strukt\Http\Exception\ServerErrorException;
use Strukt\Contract\MiddlewareInterface;
use Strukt\Contract\AbstractMiddleware;

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