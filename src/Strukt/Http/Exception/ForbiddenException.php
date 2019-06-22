<?php

namespace Strukt\Http\Exception;

class ForbiddenException extends \Exception{

	public function __construct($message="Access to resource is forbidden!"){

		$this->message = $message;
		$this->code = 403;
	}
}