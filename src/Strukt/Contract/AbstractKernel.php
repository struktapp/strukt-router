<?php

namespace Strukt\Contract;

abstract class AbstractKernel{

	protected function whichRouter(){

		return $this->router ?? $this;
	}

	public function get(string $path, callable $func, string $config = null){

		$this->whichRouter()->add(action: "GET", path:$path, func:$func, config:$config);
	}

	public function post(string $path, callable $func, string $config = null){

		$this->whichRouter()->add(action: "POST", path:$path, func:$func, config:$config);
	}

	public function delete(string $path, callable $func, string $config = null){

		$this->whichRouter()->add(action: "DELETE", path:$path, func:$func, config:$config);
	}

	public function patch(string $path, callable $func, string $config = null){

		$this->whichRouter()->add(action: "PATCH", path:$path, func:$func, config:$config);
	}

	public function put(string $path, callable $func, string $config = null){

		$this->whichRouter()->add(action: "PUT", path:$path, func:$func, config:$config);
	}

	public function any(string $path, callable $func, string $config = null){

		$this->whichRouter()->add(action: "ANY", path:$path, func:$func, config:$config);
	}

	public function options(string $path, callable $func, string $config = null){

		$this->whichRouter()->add(action: "OPTIONS", path:$path, func:$func, config:$config);
	}
}