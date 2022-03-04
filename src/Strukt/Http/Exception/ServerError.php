<?php

namespace Strukt\Http\Exception;

use Strukt\Contract\AbstractHttpException;

class ServerError extends AbstractHttpException{

	public function __construct($message="Server Error!"){

		$this->message = $message;
		$this->code = 404;
	}
}