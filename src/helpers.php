<?php

use Strukt\Cmd;
use Strukt\Router\UrlMatcher;
use Strukt\Http\Response\Json as JsonResponse;
use Strukt\Http\Response\Plain as PlainResponse;
use Strukt\Http\Response\Redirect as RedirectResponse;
use Strukt\Http\Response\File as FileResponse;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;

helper("router");

if(helper_add("matcher")){

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


if(helper_add("response")){

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

				if(empty($content))
					$content = "Nothing was returned!";

				return new PlainResponse($content, $this->code, $this->headers);
			}

			public function goto(string $url){

				return new RedirectResponse($url, 302, $this->headers);	
			}

			public function file(string $path, string $filename){

				$download = new FileResponse($path, $this->code, $this->headers);
				$download->setContentDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT, $filename);

				return $download;
			}	
		};
	}
}