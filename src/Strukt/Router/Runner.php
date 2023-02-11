<?php

namespace Strukt\Router;

use Strukt\Contract\Http\ResponseInterface;
use Strukt\Contract\Http\RequestInterface;

class Runner{

 	/** @var callable[] */
 	private $queue;
 
 	public function __construct(array $queue){

 		$this->queue = $queue;
 	}
 
 	public function __invoke(RequestInterface $request, ResponseInterface $response){

 		$middleware = array_shift($this->queue);

 		if ($middleware) {

            return $middleware($request, $response, $this);
        }

        // $response->sendHeaders();

        return $response;
 	}
}
