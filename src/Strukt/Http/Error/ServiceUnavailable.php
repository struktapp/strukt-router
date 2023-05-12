<?php

namespace Strukt\Http\Error;

use Strukt\Http\Response\Plain;

class ServerUnavailable extends Any{

	public function __construct(string $message="Server Unavailable!", array $headers = []){

		parent::__construct($message, 503, $headers);
	}
}