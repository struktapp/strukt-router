<?php

namespace Strukt\Router\Middleware;

use Strukt\Contract\ResponseInterface;
use Strukt\Http\Response;
use Strukt\Http\Request;
use Strukt\Http\Exception\NotFoundException;
use Strukt\Http\Exception\UnauthorizedException;
use Strukt\Core\Registry;
use Strukt\Contract\AbstractMiddleware;
use Strukt\Contract\MiddlewareInterface;

class Router extends AbstractMiddleware implements MiddlewareInterface{

	private $router;

	public function __construct(){

		$this->router = $this->core()->get("strukt.router");
	}

	public function __invoke(Request $request, ResponseInterface $response, callable $next){

		$uri = $request->getRequestUri();
		if(!is_null(parse_url($uri, PHP_URL_QUERY)))
			list($uri, $qs) = explode("?", $uri);

	 	$method = $request->getMethod();

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
			foreach($params as $name=>$type)
				if($type == Request::class)
					$route->setParam($name, $request);

			$headers = $response->headers->all();

	 		$response = $route->exec();
	 		if($response instanceof ResponseInterface)
	 			$response->headers->add($headers);
	 		
	 		if(is_string($response))
	 			$response = new Response($response, 200, $headers);		 
	 	}
	 	catch(\Exception $e){

	 		$code = 500;
	 		if($e->getCode() > 1)
	 			$code = $e->getCode();

	 		$response = new Response($e->getMessage(), $code);
	 	}

		return $next($request, $response);
	}
}