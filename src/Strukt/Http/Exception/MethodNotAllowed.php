<?php

namespace Strukt\Http\Exception;

class MethodNotAllowed extends \Exception{

    public function __construct($message = "", $code = 0, Throwable $previous = null) {
    
    	$code = 405;
		$message = "Method Not Allowed!";

        parent::__construct($message, $code, $previous);
    }
}