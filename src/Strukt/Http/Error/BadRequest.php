<?php

namespace Strukt\Http\Error;

use Strukt\Http\Response\Plain;

class BadRequest extends Plain implements HttpError{

	public function __construct(string $message="Bad Request!", array $headers = []){

		parent::__construct($message, 400, $headers);
	}
}