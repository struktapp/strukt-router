<?php

namespace Strukt;

use Strukt\Core\Registry;

class Env{

	public static function get($key){

		return Registry::getInstance()->get(sprintf("env.%s", $key));
	}

	public static function set($key, $val){

		if(!is_string($key) && !is_string($val))
			throw new \Exception(sprintf("%s::set(key,val) key and val must be strings!", 
											get_class($this)));
			
		return Registry::getInstance()->set(sprintf("env.%s", $key), $val);
	}
}