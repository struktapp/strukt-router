<?php

namespace Strukt\Router\Middleware;

use Strukt\Http\Response\Json as JsonResponse;
use Strukt\Http\Response\Plain as PlainResponse;
use Strukt\Contract\Http\RequestInterface;
use Strukt\Contract\Http\ResponseInterface;
use Strukt\Contract\Middleware\MiddlewareInterface;
use Strukt\Cmd;
use Strukt\Router\UrlMatcher;
use Strukt\Http\Error\MethodNotAllowed;
use Strukt\Http\Error\NotFound;

/**
* @Name(router)
* @Required()
*/
class Router implements MiddlewareInterface{

	private $event;

	public function __construct(){

		//
	}

	public function __invoke(RequestInterface $request, 
								ResponseInterface $response, callable $next){

		$uri = $request->getRequestUri();
		if(!is_null(parse_url($uri, PHP_URL_QUERY)))
			list($uri, $qs) = explode("?", $uri);

	 	$method = $request->getMethod();
	 	$headers = $response->headers->all();
	 	
		$matcher = matcher();
		$match = $matcher->which($uri);
		if(is_null($match))
			return new NotFound;

		$token = arr(["path"=>$match, "action"=>$method])->tokenize();

		$event = event($token);
		if(is_null($event))
			return new MethodNotAllowed;

		$params = $matcher->params();
		$params = arr($event->getParams())->each(function($name, $type) use($request, $response, $params){

			if(class_exists($type)){

				$interface = @end(class_implements($type));
				if($interface == RequestInterface::class)
					return $request;

				if($interface == ResponseInterface::class)
					return $response;
			}

			return $params[$name];
		});
		
		$params = $params->yield();
		if(!empty($params))
			$event = $event->applyArgs($params);

		$response = $event->exec();

		if(is_string($response))
	 		$response = new PlainResponse($response, 200, $headers);

	 	if(is_array($response))
	 		$response = new JsonResponse($response, 200, $headers);

		return $next($request, $response);
	}
}