<?php

namespace Strukt\Http\Error;

use Strukt\Http\Response\Plain;
use Strukt\Contract\Http\Error\HttpErrorInterface;

class Any extends Plain implements HttpErrorInterface{

	private static $codes = array(

		400 => "Bad Request",
		401 => "Unauthorized",
		403 => "Forbidden",
		404 => "Not Found",
		405 => "Method Not Allowed",
		500 => "Server Error",
		503 => "Service Unavailable"
	);

	public function __construct(string|array $message, int $code, array $headers = []){

		if(!self::isCode($code))
			$code = 500;

		if(\Strukt\Env::has("json_validation_err")){

			if(env("json_validation_err")){

				$headers["Content-Type"] = "application/json";

				$tmp = $message;
				$message = ["success"=>false, "data"=>[]];
				if(is_array($tmp))
					$message = array_merge($message, $tmp);

				if(is_string($tmp))
					$message["message"] = $tmp;
					
				$message = json($message)->encode();
			}
		}

		parent::__construct($message, $code, $headers);
	}

	public static function isCode($code):bool{

		return array_key_exists($code, static::$codes);
	}
}