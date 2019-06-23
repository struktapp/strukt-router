<?php

namespace Strukt\Router\Middleware;

use Strukt\Http\Response;
use Strukt\Http\Request;
use Strukt\Http\Exception\ServerErrorException;
use Strukt\Contract\MiddlewareInterface;

class ExceptionHandler implements MiddlewareInterface{

	private $is_dev = false;

	public function __construct(string $is_dev){

		$this->is_dev = $is_dev;
	}
	public function __invoke(Request $request, Response $response, callable $next){

		try {
			
			$response = $next($request, $response);
		} 
		catch (ServerErrorException $e){

			if ($this->is_dev){

				$response = new Response($e->getMessage(), $e->getCode());
			} 
			else{
			
				$response = new Response('Im sorry, try again later.', $e->getCode());
			}
		}

		return $response;
	}
}