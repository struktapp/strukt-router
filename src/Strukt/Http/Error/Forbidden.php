<?php

namespace Strukt\Http\Error;

use Strukt\Http\Response\Plain;

class Forbidden extends Any{

	public function __construct(string $message="Forbidden Resource!", array $headers = []){

		parent::__construct($message, 403, $headers);
	}
}