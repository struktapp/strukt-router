<?php

namespace Strukt\Http\Session;

use Strukt\Contract\Http\SessionInterface;

class ArrayCache extends AbstractSession{

	private $bag;

	public function __construct(){

		$this->bag = [];
	}

	public function get(/*string*/ $name, $default = null){

		return $this->bag[$name];
	}

	public function set(/*string*/ $name, $value){

		$this->bag[$name] = $value;
	}

	public function start():bool{

		return true;
	}

	public function has($name):bool{

		return array_key_exists($name, $this->bag);
	}
}