<?php

namespace Strukt\Http;

use Strukt\Contract\Http\Error\HttpErrorInterface;
use Strukt\Contract\Http\ResponseInterface;
use Strukt\Http\Response\Json as JsonResponse;
use Strukt\Type\Json;

class Exec{

	public static function make(ResponseInterface $response){

		$useJson = false;
		if(\Strukt\Reg::exists("strukt.json_err"))
			$useJson = \Strukt\Reg::get("strukt.json_err");

		return new class($response, $useJson){

			private $response;
			private $sendHeaders = false;
			private $useJson = false;

			public function __construct($response, $json = false){

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

		 			$content = $this->response->getContent();
		 			$isJson = Json::isJson($content);
		 			
		 			if($isJson)
		 				$content = Json::decode($content);
		 			
		 			if(!$isJson)
			 			$content = array(

			 				"message"=>$content,
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