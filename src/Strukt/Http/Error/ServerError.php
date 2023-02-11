<?php

namespace Strukt\Http\Error;

use Strukt\Http\Response\Plain;

class ServerError extends Any{

	public function __construct(string $message="Server Error!", array $headers = []){

		parent::__construct($message, 500, $headers);
	}
}