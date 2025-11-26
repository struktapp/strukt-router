<?php

namespace Strukt\Http\Session;

use Strukt\Contract\Http\SessionInterface;

/**
 * @author Moderator <pitsolu@gmail.com>
 */
class ArrayCache extends AbstractSession{

	public static $bag;

	public function __construct(){

		if(empty(static::$bag))
			static::$bag = [];
	}

	/**
     * @param $name
     * @param $default
     * 
     * @return mixed
     */
	public function get(/*string*/ $name, $default = null):mixed{

		return static::$bag[$name];
	}

    /**
     * @param $name
     * @param $value
     * 
     * @return void
     */
	public function set(/*string*/ $name, $value):void{

		static::$bag[$name] = $value;
	}

    /**
     * @return bool
     */
	public function start():bool{

		return true;
	}

	/**
     * @param $name
     * 
     * @return bool
     */
	public function has($name):bool{

		return array_key_exists($name, static::$bag);
	}
}