<?php

namespace Strukt\Middleware;

use Strukt\Http\Response\Plain as Response;

use Strukt\Contract\Http\RequestInterface;
use Strukt\Contract\Http\ResponseInterface;
use Strukt\Contract\Middleware\AbstractMiddleware;
use Strukt\Contract\Middleware\MiddlewareInterface;

use Strukt\Http\Error\NotFound;
use Strukt\Http\Error\Unauthorized;
use Strukt\Http\Error\ServerError;
use Strukt\Contract\Http\Error\HttpErrorInterface;
use Strukt\Http\Error\Any as HttpError;
use Strukt\Http\Exec as HttpExec;

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
	 		
	 		$route = $this->router->withMethod($method)->getRoute($uri);
	 		if(is_null($route))
	 			HttpExec::make(new NotFound)->withHeaders()->run();

 			$permissions = [];
 			if($this->core()->exists("@strukt.permissions"))
 				$permissions = $this->core()->get("@strukt.permissions");

			$routeName = $route->getName();
			if(!empty($routeName))
				if(!in_array($routeName, $permissions))
					HttpExec::make(new Unauthorized)->withHeaders()->run();

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
	 		if(HttpError::isCode($e->getCode()))
	 			$code = $e->getCode();

	 		$response = new HttpError($e->getMessage(), $code);
	 	}

	 	if($response instanceof HttpErrorInterface)
	 		HttpExec::make($response)->withHeaders()->run();

		return $next($request, $response);
	}
}