<?php

namespace Strukt\Router\Middleware;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Strukt\Router\Exception\ServerErrorException;

class ExceptionHandler implements MiddlewareInterface{

	private $env;

	public function __construct(string $env){

		$this->env = $env;
	}
	public function __invoke(Request $request, Response $response, callable $next){

		try {
			
			$response = $next($request, $response);
		} 
		catch (ServerErrorException $e){

			if ($this->env === 'dev'){

				$response = new Response($e->getMessage(), $e->getCode());
			} 
			else{
			
				$response = new Response('Im sorry, try again later.', $e->getCode());
			}
		}

		return $response;
	}
}