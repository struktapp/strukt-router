<?php

namespace Strukt\Http\Error;

use Strukt\Http\Response\Plain;

class ServiceUnavailable extends Any{

	public function __construct(string $message="Service Unavailable!", array $headers = []){

		parent::__construct($message, 503, $headers);
	}
}