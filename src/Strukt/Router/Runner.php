<?php

namespace Strukt\Router;

use Strukt\Contract\Http\ResponseInterface;
use Strukt\Contract\Http\RequestInterface;

class Runner{

	/**
	* @param $queue[] callable middlewares
	*/
 	private $queue;
 
 	public function __construct(array $queue){

 		$this->queue = $queue;
 	}
 
 	public function __invoke(RequestInterface $request, ResponseInterface $response){
 		
 		$middleware = array_shift($this->queue);

 		if ($middleware)
            return (new $middleware())($request, $response, $this);

        return $response;
 	}
}
