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
		500 => "Server Error"
	);

	public function __construct(string $message, int $code, array $headers = []){

		if(!self::isCode($code))
			$code = 500;

		parent::__construct($message, $code, $headers);
	}

	public static function isCode($code):bool{

		return array_key_exists($code, static::$codes);
	}
}