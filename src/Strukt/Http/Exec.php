<?php

namespace Strukt\Http;

use Strukt\Http\Error\HttpError;
use Strukt\Contract\Http\ResponseInterface;
use Strukt\Http\Response\Json as JsonResponse;

class Exec{

	private static $use_jsonerr = false;

	public static function withJsonError(bool $use_jsonerr = true){

		static::$use_jsonerr = $use_jsonerr;
	}

	public static function make(ResponseInterface $response){

		return new class($response, static::$use_jsonerr){

			private $response;
			private $send_headers = false;

			public function __construct($response, $use_jsonerr){

				$this->response = $response;
				$this->use_jsonerr = $use_jsonerr;
			}

			public function withHeaders(){

				$this->send_headers = true;

				return $this;
			}

			public function useJsonError(){

				if($this->response instanceof HttpError){

		 			$headers = $this->response->headers->all();
		 			$code = $this->response->getStatusCode();
		 			
		 			$content = array(

		 				"message"=>$this->response->getContent(),
		 				"success"=>false,
		 				"code"=>$code
		 			);

		 			$this->response = new JsonResponse($content, $code, $headers);
		 		}

		 		return $this;
			}

			public function run(){

				if($this->use_jsonerr)
					$this->useJsonError();

				if($this->send_headers)
					$this->response->sendHeaders();

				exit($this->response->getContent());
			}
		};
	}
}