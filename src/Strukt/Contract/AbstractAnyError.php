<?php

namespace Strukt\Contract;

use Strukt\Http\Error\Any as HttpAnyError;
use Strukt\Contract\Http\Error\HttpErrorInterface;

/**
 * @author Moderator <pitsolu@gmail.com>
 */
abstract class AbstractAnyError extends HttpAnyError implements HttpErrorInterface{

	/**
	 * @param string|array $message
	 * @param array $headers
	 */
	public function __construct(string|array $message, array $headers = []){

		$class = get_called_class();

		$code = static::$codes[$class];

		parent::__construct($message, $code, $headers);
	}
}