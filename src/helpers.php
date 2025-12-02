<?php

use Strukt\Http\Response\Json as JsonResponse;
use Strukt\Http\Response\Plain as PlainResponse;
use Strukt\Http\Response\Redirect as RedirectResponse;
use Strukt\Http\Response\File as FileResponse;
use Strukt\Http\Response\DownloadInterface;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Strukt\Http\Request;
use Strukt\Contract\MatcherInterface;
use Strukt\Contract\Http\ResponseInterface;
use Strukt\Contract\Http\RequestInterface;
use Strukt\Router\UrlMatcher;
use Strukt\Raise;

helper("router");

alias("form", Strukt\Contract\FormInterface::class);
alias("request", Strukt\Contract\Http\RequestInterface::class);
alias("response", Strukt\Contract\Http\ResponseInterface::class);
alias("session", Strukt\Contract\Http\SessionInterface::class);
alias("middleware", Strukt\Contract\MiddlewareInterface::class);
// alias("provider", Strukt\Contract\ProviderInterface::class);

app("response", [

	"plain"=>Strukt\Http\Response\Plain::class,
	"file"=>Strukt\Http\Response\File::class,
	"json"=>Strukt\Http\Response\Json::class,
	"goto"=>Strukt\Http\Response\Redirect::class
]);

app("session", [

	"mem"=>Strukt\Http\Session\Native::class,
	"cache"=>Strukt\Http\Session\ArrayCache::class
]);

app("middleware", [

	"auth"=>Strukt\Router\Middleware\Authentication::class,
	"authz"=>Strukt\Router\Middleware\Authorization::class,
	"sess"=>Strukt\Router\Middleware\Session::class
]);

if(app("provider.validator"))
	provider(app("provider.validator"));

reg("router.config", new class([]){

	private array $configs;

	public function __construct(array $configs){

		$this->configs = $configs;
	}

	public function add(string $name, mixed $config){

		if(notnull($config))
			$this->configs[$name] = $config;

		return $this;
	}

	public function get(string $name){

		return $this->configs[$name];
	}
});

reg("router.base", new class([]){

	private $methods;
	private $routes;
	private $configs;
	private $params;
	private $match;

	public function __construct(array $routes){

		$this->routes = $routes;
		$this->methods = [];
		$this->configs = [];
	}

	public function get(string $route, Closure $fn){

		$this->action("GET", $route, $fn);
	}

	public function post(string $route, Closure $fn){

		$this->action("POST", $route, $fn);
	}

	public function action(string $method, string $route, Closure $fn){

		$this->assignConfig($route);

		$this->methods[$method][] = $route;
		$this->routes["fn"][$route] = $fn;
	}

	public function assignConfig(string $route){

		$this->routes["configs"][$route] = $this->configs;
		$this->configs = [];/**reset config**/
	}

	public function setConfig(array $configs){

		$this->configs = $configs;

		return $this;
	}

	public function getMatcher(){

		if(array_key_exists("fn", $this->routes))
			return matcher(array_keys($this->routes["fn"]));

		return null;
	}

	public function which(string $url, string $method = "GET", ?array $configs = []){

		try{

			$matcher = $this->getMatcher();
			$pattern = $matcher->which($url);

			if(is_null($pattern))
				raise("", 404);

			$this->params = $matcher->params();
			if(!in_array($pattern, $this->methods[$method]))
				raise("Method Disallowed", 405);

			$this->match = $this->routes["fn"][$pattern];
			$configs = $this->routes["configs"][$pattern];
			config("user", [

				"allow"=>$configs["allow"],
				"form"=>$configs["form"]
			]);

			return $this;
		}
		catch(\Exception $e){

			if(in_array($e->getCode(), [405]))
				raise($e->getMessage(), $e->getCode());

			raise("Not found!", 404);
		}
	}

	public function getMatch():\Closure{			

		return $this->match;
	}

	public function getParams(){

		return $this->params;
	}

	public function run(RequestInterface $request, ?ResponseInterface $response = null){

		$ref = ref($this->getMatch());
		$params = $this->getParams();
		$expects = arr($ref->getRef()->getParameters())
			->map(fn($k, $v)=>[$v->getName()=>$v->getType()?->getName()])
			->level(noPrefix:true);

		$params = arr($expects)->each(fn($k, $v)=>$params[$k]??$v);
		if(in_array(RequestInterface::class, $expects))
			$params = $params->each(fn($k,$v)=>$v==RequestInterface::class?$request:$v);

		if(notnull($response))
			if(in_array(ResponseInterface::class, $expects))
				$params = $params->each(fn($k,$v)=>$v==ResponseInterface::class?$response:$v);

		$form_interface = alias("form");
		if(in_array($form_interface, $expects)){

			$form = config("user.form");
			if(notnull($form)){
				$f = new $form($request);
				if(negate($f->validate()["success"]))
					raise("Form failed validation!");

				$params = $params->each(fn($k,$v)=>$form_interface == $v?$f:$v);
			}
		}

		return $ref->invoke(...$params->yield());
	}
});

if(helper_add("router")){

	function router(?Closure $globals = null,
					?Closure $session = null,
					?Closure $roles = null,
					?Closure $permissions = null,
					?Closure $verify = null,
					?array $middlewares = null,
					?string $form = null,
					?array $allow = null){

		$is_setup = negate(arr([

			$globals, $session, $roles, $permissions, $verify, $middlewares

		])->are()->all()->null());

		$is_route = negate(arr([$form, $allow])->are()->all()->null());

		if($is_setup && $is_route)
			raise("Can only setup router or declare route, not both!");

		if($is_route && !is_null($form))
			if(!class_implements($form, Strukt\Contract\FormInterface::class))
				raise(sprintf("%s does not implement FormInterface!", $form));

		if($is_setup)
			return reg("router.config")
				->add("globals", $globals)
				->add("session", $session)
				->add("roles", $roles)
				->add("permissions", $permissions)
				->add("middlewares", $middlewares)
				->add("verify", $verify);

		$base = reg("router.base");
		if($is_route)
			$base->setConfig(["form"=>$form, "allow"=>$allow]);

		return $base;
	}

	function shutdown_handler() {
	    
	    if(negate(empty($_SERVER['REMOTE_ADDR'])))
	    	exit((new Strukt\Router\Kernel())->run());
	}

	register_shutdown_function('shutdown_handler');
}

if(helper_add("matcher")){

	function matcher(array $patterns){

		/**
		 * Example:
		 * 	$m = matcher(["/hello/{name:alpha}", "/user/{id:int}", "/", "/user/current", "say/{name}"])
		 * 	$m->which("/hello/pitsolu") // returns /hello/{name}
		 * 	$m->params(); // returns ["name"=>"pitsolu"]
		 * 
		 * @return \Strukt\Contract\MatcherInterface
		 */
		return new class($patterns){

			private $matcher;

			public function __construct($patterns){

				$this->matcher = new UrlMatcher($patterns);
			}

			/**
			 * @param string $route
			 * 
			 * @return string|null
			 */
			public function which(string $route):string|null{

				return $this->matcher->get($route);
			}

			/**
			 * @return array
			 */
			public function params():array{

				return $this->matcher->getParams();
			}
		};
	}
}

if(helper_add("response")){

	/**
	 * @param integer $code
	 * @param array $headers
	 * 
	 * @return \Strukt\Http\Response\ResponseInterface
	 */
	function response(int $code = 200, array $headers = []):ResponseInterface{

		return new class($code, $headers) implements ResponseInterface{

			private $code;
			private $headers;

			/**
			 * @param integer $code
			 * @param array $headers
			 */
			public function __construct(int $code, array $headers = []){

				$this->code = $code;
				$this->headers = $headers;
			}

			/**
			 * @param array $headers
			 * 
			 * @return static
			 */
			public function headers(array $headers):static{

				$this->headers = array_merge($this->headers, $headers);

				return $this;
			}

			/**
			 * @param array $content
			 * 
			 * @return \Strukt\Http\Response\Json
			 */
			public function json(array $content):JsonResponse{

				return new JsonResponse($content, $this->code, $this->headers);
			}

			/**
			 * @param string $content
			 * 
			 * @return \Strukt\Http\Response\Plain
			 */
			public function body(string $content):PlainResponse{

				if(empty($content))
					$content = "Nothing was returned!";

				return new PlainResponse($content, $this->code, $this->headers);
			}

			/**
			 * @param string $url
			 * 
			 * @return \Strukt\Http\Response\Redirect
			 */
			public function goto(string $url):RedirectResponse{

				return new RedirectResponse($url, 302, $this->headers);	
			}

			/**
			 * @param string $path
			 * @param string $filename
			 * 
			 * @return \Strukt\Http\Response\{File|DownloadInterface}
			 */
			public function file(string $path, string $filename):DownloadInterface{

				$download = new FileResponse($path, $this->code, $this->headers);
				$download->setContentDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT, $filename);

				return $download;
			}	
		};
	}
}


if(helper_add("request")){

	/**
	 * Example: $r = request(["username"=>"pitsolu", "password"=>"p@55w0rd"])
	 * 
	 * @param array $args
	 * @param array $headers
	 * 
	 * @return \Strukt\Http\Request
	 */
	function request(array $args = [], ?array $headers = null):Request{

		$request = new Request($args);
		if(notnull($headers))
			$request->headers->add($headers);

		return $request; 
	}
}

if(helper_add("route")){

	/**
	 * Execute route
	 * 
	 * Example: 
	 * 		$r1 = route("/")->get();
	 * 		$r2 = route("/hello/pitsolu")->get(request());
	 * 		$r3 = route("/login")->post(request(["username"=>"admin", "password"=>"p@55w0rd"]));
	 * 		$r3->getContent();
	 * 
	 * @param string $path
	 * 
	 * @return object
	 */
	function route(string $path):object{
		
		return new class($path, reg("router.base")){

			private $path;
			private $base;

			public function __construct($path, $base){

				$this->path = $path;
				$this->base = $base;
			}

			public function get(RequestInterface $request, ?ResponseInterface $response = null){

				return $this->action(request:$request, response:$response);
			}

			public function post(RequestInterface $request, ?ResponseInterface $response = null){

				return $this->action(method:"POST", request:$request, response:$response);
			}

			public function action(RequestInterface $request, 
									?ResponseInterface $response = null,
									$method = "GET"){

				return $this->base->which(url:$this->path, method:$method)->run($request, $response);
			}
		};
	}
}