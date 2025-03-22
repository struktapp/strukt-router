<?php

namespace Strukt\Router;

use Strukt\Http\Response\Plain as PlainResponse;
use Strukt\Http\Response\Json as JsonResponse;
use Strukt\Contract\Http\RequestInterface;
use Strukt\Contract\Http\ResponseInterface;
use Strukt\Contract\Http\DownloadInterface;
use Strukt\Contract\Http\Error\HttpErrorInterface;
use Strukt\Contract\Middleware\MiddlewareInterface;
use Strukt\Router\UrlMatcher;
use Strukt\Http\Error\MethodNotAllowed;
use Strukt\Http\Error\NotFound;
use Strukt\Http\Error\Unauthorized;
use Strukt\Raise;
use Strukt\Event;
use Strukt\Contract\AbstractKernel;

/**
 * @author Moderator <pitsolu@gmail.com>
 */
class Kernel extends AbstractKernel{

	protected $middlewares;
	protected $request;
	protected $permissions;
	protected $configs;

	/**
	 * @param \Strukt\Contract\Http\RequestInterface request
	 */
	public function __construct(RequestInterface $request){

		$this->request = $request;
		$this->permissions = [];
		$this->configs = [];
	}

	/**
	 * @param array providers
	 */
	public function providers(array $providers):void{

		foreach($providers as $provider){

 			$closure = \Strukt\Ref::create($provider)->make()->method("register")->getClosure();

 			call_user_func($closure);
		}
	}

	/**
	 * @param array middlewares
	 */
	public function middlewares(array $middlewares):void{

		foreach($middlewares as $middleware)
 			$this->middlewares[] = \Strukt\Ref::create($middleware)->make()->getInstance();
	}

	/**
	 * @param string $name
	 * @param callable $func
	 */
	public function inject(string $name, callable $func){

		event(sprintf("@inject.%s", $name), $func);		
	}

	/**
	* @param string $path - uri pattern
	* @param callable $func
	* @param string $action HTTP method
	* @param string $config permission|token
	*/
	public function add(string $path, callable $func, string $action="GET", ?string $config = null){

		$name = arr(["path"=>$path, "action"=>$action])->tokenize();
		$name = sprintf("type:route|%s", $name);
		$this->permissions[$name] = [];

		$allows = [];
		if(!is_null($config)){

			$config = str(trim($config));
			if($config->contains("allows:")){

				$allows = token($config->yield())->get("allows");
				if(is_string($allows))
					$allows = [$allows];
			}

			if(empty($allows))//if empty
				if(!preg_match("/\w+:\w+/", $config->yield()))
					$allows[] = $config->yield();

			if(empty($allows))//if still empty
				if($config->equals("strukt:auth"))
					$allows[] = $config->yield();

			$this->permissions[$name] = array_merge($this->permissions[$name], $allows);
			$this->configs[$name] = $config->yield();
		}

		event($name, $func);
	}

	/**
	 * @return static
	 */
	public function init():static{

		reg("@strukt.permissions", $this->permissions);
		if(!empty($this->configs))
			reg("route.configs", $this->configs);

		return $this;
	}

	/**
	 * @return string
	 */
	public function run():string{

		$this->init();

		$response = new PlainResponse;
		$uri = $this->request->getRequestUri();
		if(!is_null(parse_url($uri, PHP_URL_QUERY)))
			list($uri, $qs) = explode("?", $uri);

		$matcher = matcher();
		$match = $matcher->which($uri);
		if(is_null($match))
			$response = new NotFound;

		$is_download = false;
		if(!$response instanceof HttpErrorInterface){

			reg("route.current", $match);

			$method = $this->request->getMethod();
			$name = arr(["path"=>$match, "action"=>$method])->tokenize();
			$name = sprintf("type:route|%s", $name);

			$event = event($name);
			if(is_null($event))
				$response = new MethodNotAllowed;

			if(!$response instanceof HttpErrorInterface){
		
				$runner = new Runner($this->middlewares);
				$response = $runner($this->request, $response);
				$headers = $response->headers->all();

				if(!$response instanceof HttpErrorInterface){

					$params = $matcher->params();
					$request = $this->request;
					$params = arr($event->getParams())->each(function($name, $type) 
													use($request, $response, $params){

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
					$is_download = $response instanceof DownloadInterface;
					$code = 200;

					if(!$is_download){

						if(is_string($response))
					 		$response = new PlainResponse($response, $code, $headers);

					 	if(is_array($response))
					 		$response = new JsonResponse($response, $code, $headers);
					}
				}
			}
		}

		if(\Strukt\Env::has("res_send_headers"))
			if(env("res_send_headers"))
				$response->sendHeaders();

		if(!$is_download)
			return $response->getContent();

		return $response->sendContent();
	}
}