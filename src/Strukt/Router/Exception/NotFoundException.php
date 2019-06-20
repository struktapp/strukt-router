<?php

namespace Strukt\Router\Exception;

class NotFoundException extends \Exception{

	public function __construct($message="Not Found!"){

		$this->message = $message;
		$this->code = 404;
	}
}