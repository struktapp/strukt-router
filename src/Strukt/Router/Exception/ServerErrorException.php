<?php

namespace Strukt\Router\Exception;

class ServerErrorException extends \Exception{

	public function __construct($message="Server Error!"){

		$this->message = $message;
		$this->code = 404;
	}
}