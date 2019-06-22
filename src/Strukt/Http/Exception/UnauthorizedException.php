<?php

namespace Strukt\Http\Exception;

class UnauthorizedException extends \Exception{

	public function __construct($message="Unauthorized access!"){

		$this->message = $message;
		$this->code = 401;
	}
}