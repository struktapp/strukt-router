<?php

namespace Strukt\Http;

class Method{

	public static $methods = array(

		"ANY",
		"PUT", 
		"GET", 
		"PATH", 
		"POST", 
		"DELETE",
		"PATCH",
		"OPTIONS"
	);

	public static function getAll(){

		return static::$methods;
	}

	public static function isAllowed(string $method):bool{

		return in_array(trim(strtoupper($method)), static::$methods);
	}
}