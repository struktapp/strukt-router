<?php

namespace Strukt\Http\Error;

use Strukt\Http\Response\Plain;
use Strukt\Contract\AbstractAnyError;

class Forbidden extends AbstractAnyError{

	public function __construct(string|array $message="Forbidden Resource!", array $headers = []){

		parent::__construct($message, $headers);
	}
}