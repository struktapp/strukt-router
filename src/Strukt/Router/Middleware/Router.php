<?php

namespace Strukt\Router\Middleware;

use Strukt\Http\Response\Plain as Response;

use Strukt\Contract\Http\RequestInterface;
use Strukt\Contract\Http\ResponseInterface;
use Strukt\Contract\Middleware\AbstractMiddleware;
use Strukt\Contract\Middleware\MiddlewareInterface;

use Strukt\Http\Exception\NotFound as NotFoundException;
use Strukt\Http\Exception\Unauthorized as UnauthorizedException;
use Strukt\Contract\Http\Exception\HttpExceptionInterface;

/**
* @Name(router)
* 
*/
class Router extends AbstractMiddleware implements MiddlewareInterface{

	private $router;

	public function __construct(){

		$this->router = $this->core()->get("strukt.router");
	}

	public function __invoke(RequestInterface $request, 
								ResponseInterface $response, callable $next){

		$uri = $request->getRequestUri();
		if(!is_null(parse_url($uri, PHP_URL_QUERY)))
			list($uri, $qs) = explode("?", $uri);

	 	$method = $request->getMethod();
	 	$headers = $response->headers->all();

	 	try{
	 		
	 		$route = $this->router->getRoute($method, $uri);
	 		if(is_null($route))
	 			throw new NotFoundException();

 			$permissions = [];
 			if($this->core()->exists("@strukt.permissions"))
 				$permissions = $this->core()->get("@strukt.permissions");

			$routeName = $route->getName();
			if(!empty($routeName))
				if(!in_array($routeName, $permissions))
					throw new UnauthorizedException();

 			$params = $route->getEvent()->getParams();
 			
			foreach($params as $name=>$type){

				if(class_exists($type)){

					$interface = @reset(class_implements($type));
					if($interface == RequestInterface::class)
						$route->setParam($name, $request);

					if($interface == ResponseInterface::class)
						$route->setParam($name, $response);
				}
			}

	 		$response = $route->exec();
	 		if($response instanceof ResponseInterface)
	 			$response->headers->add($headers);
	 		
	 		if(is_string($response))
	 			$response = new Response($response, 200, $headers);
	 	}
	 	catch(\Exception $e){

	 		$code = 500;
	 		if($e instanceof HttpExceptionInterface)
	 			$code = $e->getCode();

	 		$response = new Response($e->getMessage(), $code, $headers);
	 	}

		return $next($request, $response);
	}
}