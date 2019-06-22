<?php

namespace Strukt\Router;

use Strukt\Http\Response;
use Strukt\Http\Request;

class Runner{

 	/** @var callable[] */
 	private $queue;
 
 	public function __construct(array $queue){

 		$this->queue = $queue;
 	}
 
 	public function __invoke(Request $request, Response $response){

 		$middleware = array_shift($this->queue);

 		if ($middleware) {

            return $middleware($request, $response, $this);
        }

        return $response;
 	}
}
