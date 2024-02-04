<?php

namespace Strukt\Http\Error;

use Strukt\Http\Response\Plain;
use Strukt\Contract\AbstractAnyError;

class Unauthorized extends AbstractAnyError{

	public function __construct(string|array $message="Unathorized Access!", array $headers = []){

		parent::__construct($message, $headers);
	}
}