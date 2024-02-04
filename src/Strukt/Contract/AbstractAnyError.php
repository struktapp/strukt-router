<?php

namespace Strukt\Contract;

use Strukt\Http\Error\Any as AnyError;
use Strukt\Contract\Http\Error\HttpErrorInterface;

abstract class AbstractAnyError extends AnyError implements HttpErrorInterface{

	public function __construct(string|array $message, array $headers = []){

		$class = get_called_class();

		$code = static::$codes[$class];

		parent::__construct($message, $code, $headers);
	}
}