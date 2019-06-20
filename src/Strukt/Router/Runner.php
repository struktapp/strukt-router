<?php

namespace Strukt\Router;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;

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
