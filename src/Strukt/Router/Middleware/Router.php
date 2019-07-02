<?php

namespace Strukt\Router\Middleware;

use Strukt\Http\Response;
use Strukt\Http\Request;
use Strukt\Http\Exception\NotFoundException;
use Strukt\Http\Exception\UnauthorizedException;
use Strukt\Core\Registry;
use Strukt\Contract\AbstractMiddleware;
use Strukt\Contract\MiddlewareInterface;

class Router extends AbstractMiddleware implements MiddlewareInterface{

	private $route_col;

	public function __construct(){

		$this->route_col = $this->core()->get("app.router");
	}

	public function __invoke(Request $request, Response $response, callable $next){

		$uri = $request->getRequestUri();

		if(!is_null(parse_url($uri, PHP_URL_QUERY))){

			list($uri, $qs) = explode("?", $uri);
		}

	 	$method = $request->getMethod();

	 	try{
	 		
	 		$route = $this->route_col->getRoute($method, $uri);

	 		if(!is_null($route)){

	 			if($this->core()->exists("access.permissions")){

	 				$permissions = $this->core()->get("access.permissions");

	 				$routeName = $route->getName();

	 				if(!empty($routeName)){

	 					if(!in_array($routeName, $permissions)){

	 						throw new UnauthorizedException();
	 					}
	 				}
	 			}

		 		$response = $route->setParam("request", $request)->exec();

		 		if(is_string($response)){

		 			$response = new Response($response);
		 		}
		 	}
		 	else{

		 		throw new NotFoundException();
		 	}
	 	}
	 	catch(\Exception $e){

	 		$code = 500;
	 		
	 		if($e->getCode() > 1){

	 			$code = $e->getCode();
	 		}

	 		$response = new Response($e->getMessage(), $code);
	 	}

		return $next($request, $response);
	}
}