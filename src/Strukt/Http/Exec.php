<?php

namespace Strukt\Http;

use Strukt\Http\Error\HttpErrorInterface;
use Strukt\Contract\Http\ResponseInterface;
use Strukt\Http\Response\Json as JsonResponse;

class Exec{

	private static $useJson = false;

	public static function withJsonError(bool $json = true){

		static::$useJson = $json;
	}

	public static function make(ResponseInterface $response){

		return new class($response, static::$useJson){

			private $response;
			private $sendHeaders = false;

			public function __construct($response, $json){

				$this->response = $response;
				$this->useJson = $json;
			}

			public function withHeaders(){

				$this->sendHeaders = true;

				return $this;
			}

			public function useJsonError(){

				if($this->response instanceof HttpErrorInterface){

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

				if($this->useJson)
					$this->useJsonError();

				if($this->sendHeaders)
					$this->response->sendHeaders();

				exit($this->response->getContent());
			}
		};
	}
}