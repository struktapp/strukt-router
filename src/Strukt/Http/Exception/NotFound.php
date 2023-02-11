<?php

namespace Strukt\Http\Exception;

class NotFound extends \Exception{

    public function __construct($message = "", $code = 0, Throwable $previous = null) {
    
    	$code = 404;
		$message = "Not Found";

        parent::__construct($message, $code, $previous);
    }
}