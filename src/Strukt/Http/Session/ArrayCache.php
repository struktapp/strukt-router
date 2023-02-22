<?php

namespace Strukt\Http\Session;

use Strukt\Contract\Http\SessionInterface;

class ArrayCache implements SessionInterface{

	private $bag;

	public function __construct(){

		$this->bag = [];
	}

	public function get(string $key){

		return $this->bag[$key];
	}

	public function set(string $key, $val){

		$this->bag[$key] = $val;
	}

	public function start():bool{

		return true;
	}

	public function has($name):bool{

		return array_key_exists($name, $this->bag);
	}
}