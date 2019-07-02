<?php

namespace Strukt\Router\Middleware;

use Strukt\Http\Response;
use Strukt\Http\Request;
use Strukt\Http\Exception\ServerErrorException;
use Strukt\Contract\MiddlewareInterface;
use Strukt\Env;
use Strukt\Contract\AbstractMiddleware;

class ExceptionHandler extends AbstractMiddleware implements MiddlewareInterface{

	private $is_dev;

	public function __construct(){

		$this->is_dev = Env::get("is_dev");
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