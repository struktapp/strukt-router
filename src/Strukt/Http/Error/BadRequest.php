<?php

namespace Strukt\Http\Error;

use Strukt\Http\Response\Plain;
use Strukt\Contract\AbstractAnyError;

class BadRequest extends AbstractAnyError{

	public function __construct(string|array $message="Bad Request!", array $headers = []){

		parent::__construct($message, $headers);
	}
}