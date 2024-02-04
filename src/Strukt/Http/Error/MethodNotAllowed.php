<?php

namespace Strukt\Http\Error;

use Strukt\Http\Response\Plain;
use Strukt\Contract\AbstractAnyError;

class MethodNotAllowed extends AbstractAnyError{

	public function __construct(string|array $message="Method Not Allowed!", array $headers = []){

		parent::__construct($message, $headers);
	}
}