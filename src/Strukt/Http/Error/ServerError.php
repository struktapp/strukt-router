<?php

namespace Strukt\Http\Error;

use Strukt\Http\Response\Plain;
use Strukt\Contract\AbstractAnyError;

class ServerError extends AbstractAnyError{

	public function __construct(string|array $message="Server Error!", array $headers = []){

		parent::__construct($message, $headers);
	}
}