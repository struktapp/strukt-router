<?php

use Strukt\Cmd;
use Strukt\Router\UrlMatcher;
use Strukt\Http\Response\Json as JsonResponse;
use Strukt\Http\Response\Plain as PlainResponse;
use Strukt\Http\Response\Redirect as RedirectResponse;

if(!function_exists("matcher")){

	function matcher(){

		return new class(){

			private $matcher;

			public function __construct(){

				$patterns = arr(Cmd::ls("^type:route"))->each(function($k, $v){

					return token($v)->get("path");
				});

				$this->matcher = new UrlMatcher($patterns->yield());
			}

			public function which(string $route){

				return $this->matcher->whichPattern($route);
			}

			public function params(){

				return $this->matcher->getParams();
			}
		};
	}
}


if(!function_exists("response")){

	function response($code = 200, array $headers = []){

		return new class($code, $headers){

			private $code;
			private $headers;

			public function __construct($code, array $headers = []){

				$this->code = $code;
				$this->headers = $headers;
			}

			public function headers(array $headers){

				$this->headers = array_merge($this->headers, $headers);

				return $this;
			}

			public function json(array $content){

				return new JsonResponse($content, $this->code, $this->headers);
			}

			public function body(string $content){

				return new PlainResponse($content, $this->code, $this->headers);
			}

			public function redirect(string $content){

				return new RedirectResponse($content, 302, $this->headers);	
			}
		};
	}
}