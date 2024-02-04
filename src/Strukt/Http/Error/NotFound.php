<?php

namespace Strukt\Http\Error;

use Strukt\Http\Response\Plain;
use Strukt\Contract\AbstractAnyError;

class NotFound extends AbstractAnyError{

	public function __construct(string|array $message="Not Found!", array $headers = []){

		parent::__construct($message, $headers);
	}
}