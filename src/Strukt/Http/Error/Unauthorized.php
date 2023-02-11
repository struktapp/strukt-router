<?php

namespace Strukt\Http\Error;

use Strukt\Http\Response\Plain;

class Unauthorized extends Any{

	public function __construct(string $message="Unathorized Access!", array $headers = []){

		parent::__construct($message, 401, $headers);
	}
}