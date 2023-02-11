<?php

namespace Strukt\Http\Error;

use Strukt\Http\Response\Plain;

class MethodNotAllowed extends Any{

	public function __construct(string $message="Method Not Allowed!", array $headers = []){

		parent::__construct($message, 405, $headers);
	}
}