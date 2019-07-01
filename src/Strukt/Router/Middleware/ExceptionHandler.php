<?php

namespace Strukt\Router\Middleware;

use Strukt\Http\Response;
use Strukt\Http\Request;
use Strukt\Http\Exception\ServerErrorException;
use Strukt\Contract\MiddlewareInterface;
use Strukt\Env;

class ExceptionHandler implements MiddlewareInterface{

	public function __invoke(Request $request, Response $response, callable $next){

		try {
			
			$response = $next($request, $response);
		} 
		catch (ServerErrorException $e){

			if (Env::get("is_dev")){

				$response = new Response($e->getMessage(), $e->getCode());
			} 
			else{
			
				$response = new Response('Im sorry, try again later.', $e->getCode());
			}
		}

		return $response;
	}
}