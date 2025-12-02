<?php

namespace Strukt\Router;

use Strukt\Http\Response\Plain as PlainResponse;
use Strukt\Http\Response\Json as JsonResponse;
use Strukt\Contract\Http\RequestInterface;
use Strukt\Contract\Http\ResponseInterface;
use Strukt\Contract\Middleware\MiddlewareInterface;

/**
 * @author Moderator <pitsolu@gmail.com>
 */
class Kernel{

	protected $middlewares;
	protected $request;
	protected $permissions;

	public function __construct(?RequestInterface $globals = null){

		$rconfig = reg("router.config");

		$this->request = $globals;		
		if(is_null($globals))
			$this->request = $rconfig->get("globals")();

		$middlewares = $rconfig->get("middlewares");

		$this->permissions = [];
		foreach($middlewares as $middleware)
 			$this->middlewares[] = \Strukt\Ref::create($middleware)->make()->getInstance();
	}

	/**
	 * @return string
	 */
	public function run():string{

		$response = new PlainResponse;
		$method = $this->request->getMethod();
		$uri = $this->request->getRequestUri();

		if(!is_null(parse_url($uri, PHP_URL_QUERY)))
			list($uri, $qs) = explode("?", $uri);

		try{

			$rbase = reg("router.base")->which($uri, $method);

			$runner = new Runner($this->middlewares);
			$response = $runner($this->request, $response);
			$headers = $response->headers->all();

			$expected = $rbase->run($this->request, $response);
			if(is_string($expected))
				$response = new PlainResponse($expected, 200, $headers);

			if($expected instanceof ResponseInterface)
				$response = $expected;

			$response->sendHeaders();
			return $response->getContent();

		}
		catch(\Exception $e){

			return $e->getMessage();
		}
	}
}