<?php

use Strukt\Cmd;
use Strukt\Router\UrlMatcher;
use Strukt\Http\Response\Json as JsonResponse;
use Strukt\Http\Response\Plain as PlainResponse;
use Strukt\Http\Response\Redirect as RedirectResponse;
use Strukt\Http\Response\File as FileResponse;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Strukt\Contract\MatcherInterface;
use Strukt\Http\Response\DownloadInterface;
use Strukt\Contract\ResponseInterface;

helper("router");

if(helper_add("matcher")){

	/**
	 * Example:
	 * 	$m = matcher()
	 * 	$m->which("/hello/pitsolu") // returns /hello/{name}
	 * 	$m->params(); // returns ["name"=>"pitsolu"]
	 * 
	 * @return \Strukt\Contract\MatcherInterface
	 */
	function matcher():MatcherInterface{

		return new class() implements MatcherInterface{

			private $matcher;

			public function __construct(){

				$patterns = arr(Cmd::ls("^type:route"))->each(function($k, $v){

					return token($v)->get("path");
				});

				$this->matcher = new UrlMatcher($patterns->yield());
			}

			/**
			 * @param string $route
			 * 
			 * @return string|null
			 */
			public function which(string $route):string|null{

				return $this->matcher->whichPattern($route);
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