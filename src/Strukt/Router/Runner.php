<?php

namespace Strukt\Router;

use Strukt\Contract\Http\ResponseInterface;
use Strukt\Contract\Http\RequestInterface;
use Strukt\Http\Response\Plain;

/**
 * @author Moderator <pitsolu@gmail.com>
 */
class Runner{

	/**
	* @param $queue[] callable middlewares
	*/
 	private $queue;
 
 	/**
 	 * @param array $queue
 	 */
 	public function __construct(array $queue){

 		$this->queue = $queue;
 	}
 
 	/**
 	 * @param \Strukt\Contract\Http\RequestInterface $request
 	 * @param \Strukt\Contract\Http\ResponseInterface $response
 	 * 
 	 * @return \Strukt\Http\Response\Plain
 	 */
 	public function __invoke(RequestInterface $request, ResponseInterface $response):Plain{
 		
 		$middleware = array_shift($this->queue);

 		if ($middleware)
            return $middleware($request, $response, $this);

        return $response;
 	}
}
