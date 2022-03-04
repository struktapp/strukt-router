<?php

namespace Strukt\Http\Exception;

use Strukt\Contract\AbstractHttpException;

class NotFound extends AbstractHttpException{

	public function __construct($message="Not Found!"){

		$this->message = $message;
		$this->code = 404;
	}
}