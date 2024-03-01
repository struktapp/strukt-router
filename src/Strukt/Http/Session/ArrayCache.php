<?php

namespace Strukt\Http\Session;

use Strukt\Contract\Http\SessionInterface;

class ArrayCache extends AbstractSession{

	private static $bag;

	public function __construct(){

		if(empty(static::$bag))
			static::$bag = [];
	}

	public function get(/*string*/ $name, $default = null){

		return static::$bag[$name];
	}

	public function set(/*string*/ $name, $value){

		static::$bag[$name] = $value;
	}

	public function start():bool{

		return true;
	}

	public function has($name):bool{

		return array_key_exists($name, static::$bag);
	}
}