<?php

namespace Strukt\Http\Exception;

use Strukt\Contract\Http\Exception\AbstractHttpException;

class Forbidden extends AbstractHttpException{

	public function __construct($message="Forbidden Resource!"){

		$this->message = $message;
		$this->code = 403;
	}
}