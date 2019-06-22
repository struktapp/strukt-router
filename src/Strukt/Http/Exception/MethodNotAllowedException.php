<?php

namespace Strukt\Http\Exception;

class MethodNotAllowedException extends \Exception{

	public function __construct($message="Method Not Allowed!"){

		$this->message = $message;
		$this->code = 405;
	}
}