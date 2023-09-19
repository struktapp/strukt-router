<?php

namespace Strukt\Router;

use Strukt\Contract\Http\ResponseInterface;
use Strukt\Contract\Http\RequestInterface;

class Runner{

	/**
	* @param $queue callable middlewares
	*/
 	private $queue;
 
 	public function __construct(array $queue){

 		$this->queue = $queue;
 	}
 
 	public function __invoke(RequestInterface $request, ResponseInterface $response){

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
		/**
		* @todo put token in session so as to access
		*	in authorization middleware for scanning permissions array with key
		*/

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

 		$middleware = array_shift($this->queue);

 		if ($middleware)
            return (new $middleware())($request, $response, $this);

        return $response;
 	}
}
