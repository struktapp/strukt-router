<?php

namespace Strukt\Http\Error;

use Strukt\Http\Response\Plain;
use Strukt\Contract\AbstractAnyError;

class ServiceUnavailable extends AbstractAnyError{

	public function __construct(string|array $message="Service Unavailable!", array $headers = []){

		parent::__construct($message, $headers);
	}
}