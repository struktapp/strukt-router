<?php

namespace Strukt\Contract;

/**
 * @author Moderator <pitsolu@gmail.com>
 */
abstract class AbstractKernel{

	/**
	 * @return \Strukt\Contract\AbstractKernel
	 */
	protected function router():self{

		return $this->router ?? $this;
	}

	/**
	 * @param string $path
	 * @param callable $func
	 * @param string $config
	 * 
	 * @return void
	 */
	public function get(string $path, callable $func, ?string $config = null):void{

		$this->router()->add(...array($path, $func, "GET", $config));
	}

	/**
	 * @param string $path
	 * @param callable $func
	 * @param string $config
	 * 
	 * @return void
	 */
	public function post(string $path, callable $func, ?string $config = null):void{

		$this->router()->add(...array($path, $func, "POST", $config));
	}

	/**
	 * @param string $path
	 * @param callable $func
	 * @param string $config
	 * 
	 * @return void
	 */
	public function delete(string $path, callable $func, ?string $config = null):void{

		$this->router()->add(...array($path, $func, "DELETE", $config));
	}

	/**
	 * @param string $path
	 * @param callable $func
	 * @param string $config
	 * 
	 * @return void
	 */
	public function patch(string $path, callable $func, ?string $config = null):void{

		$this->router()->add(...array($path, $func, "PATCH", $config));
	}

	/**
	 * @param string $path
	 * @param callable $func
	 * @param string $config
	 * 
	 * @return void
	 */
	public function put(string $path, callable $func, ?string $config = null):void{

		$this->router()->add(...array($path, $func, "PUT", $config));
	}

	/**
	 * @param string $path
	 * @param callable $func
	 * @param string $config
	 * 
	 * @return void
	 */
	public function any(string $path, callable $func, ?string $config = null):void{

		$this->router()->add(...array($path, $func, "ANY", $config));
	}

	/**
	 * @param string $path
	 * @param callable $func
	 * @param string $config
	 * 
	 * @return void
	 */
	public function options(string $path, callable $func, ?string $config = null):void{

		$this->router()->add(...array($path, $func, "OPTIONS", $config));
	}
}