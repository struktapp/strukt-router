<?php

namespace Strukt\Router\Middleware;

use Strukt\Contract\ResponseInterface;
use Strukt\Http\Response;
use Strukt\Http\Request;
use Strukt\Http\Exception\ServerErrorException;
use Strukt\Contract\MiddlewareInterface;
use Strukt\Env;
use Strukt\Contract\AbstractMiddleware;

class ExceptionHandler extends AbstractMiddleware implements MiddlewareInterface{

	public function __construct(){

		//
	}

	public function __invoke(Request $request, ResponseInterface $response, callable $next){

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