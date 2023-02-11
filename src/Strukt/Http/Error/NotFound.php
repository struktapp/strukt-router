<?php

namespace Strukt\Http\Error;

use Strukt\Http\Response\Plain;

class NotFound extends Plain implements HttpError{

	public function __construct(string $message="Not Found!", array $headers = []){

		parent::__construct($message, 404, $headers);
	}
}