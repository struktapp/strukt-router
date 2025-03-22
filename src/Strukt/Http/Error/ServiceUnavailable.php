<?php

namespace Strukt\Http\Error;

use Strukt\Http\Response\Plain;
use Strukt\Contract\AbstractAnyError;

/**
 * @author Moderator <pitsolu@gmail.com>
 */
class ServiceUnavailable extends AbstractAnyError{

	/**
	 * @param string|array $message
	 * @param array $headers
	 */
	public function __construct(string|array $message="Service Unavailable!", array $headers = []){

		parent::__construct($message, $headers);
	}
}