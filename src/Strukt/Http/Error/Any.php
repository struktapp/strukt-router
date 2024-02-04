<?php

namespace Strukt\Http\Error;

use Strukt\Http\Response\Plain;

class Any extends Plain{

	protected static $codes = array(

		\Strukt\Http\Error\BadRequest::class => 400,
		\Strukt\Http\Error\Unauthorized::class => 401,
		\Strukt\Http\Error\Forbidden::class => 403,
		\Strukt\Http\Error\NotFound::class => 404,
		\Strukt\Http\Error\MethodNotAllowed::class => 405,
		\Strukt\Http\Error\ServerError::class => 500,
		\Strukt\Http\Error\ServiceUnavailable::class => 503
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

		return in_array($code, static::$codes);
	}
}