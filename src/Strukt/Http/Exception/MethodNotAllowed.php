<?php

namespace Strukt\Http\Exception;

use Strukt\Contract\Http\Exception\AbstractHttpException;

class MethodNotAllowed extends AbstractHttpException{

	public function __construct($message="Method Not Allowed!"){

		$this->message = $message;
		$this->code = 405;
	}
}