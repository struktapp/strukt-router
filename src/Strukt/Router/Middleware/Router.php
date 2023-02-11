<?php

namespace Strukt\Router\Middleware;

use Strukt\Http\Response\Plain as Response;
// use Strukt\Http\Response\Json as JsonResponse;

use Strukt\Contract\Http\RequestInterface;
use Strukt\Contract\Http\ResponseInterface;
use Strukt\Contract\Middleware\AbstractMiddleware;
use Strukt\Contract\Middleware\MiddlewareInterface;

use Strukt\Http\Error\NotFound;
use Strukt\Http\Error\Unauthorized;
use Strukt\Http\Error\ServerError;
use Strukt\Http\Error\HttpError;
use Strukt\Http\Exec;

/**
* @Name(router)
* @Required()
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
	 			Exec::make(new NotFound)->withHeaders()->run();

 			$permissions = [];
 			if($this->core()->exists("@strukt.permissions"))
 				$permissions = $this->core()->get("@strukt.permissions");

			$routeName = $route->getName();
			if(!empty($routeName))
				if(!in_array($routeName, $permissions))
					Exec::make(new Unauthorized)->withHeaders()->run();

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

	 		$response = new ServerError($e->getMessage());
	 	}

	 	if($response instanceof HttpError)
	 		Exec::make($response)->withHeaders()->run();

		return $next($request, $response);
	}
}