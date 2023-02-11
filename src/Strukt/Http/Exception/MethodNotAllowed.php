<?php

namespace Strukt\Http\Exception;

class MethodNotAllowed extends \Exception{

	public function construct(){

		$this->code = 405;
		$this->message = "Method Not Allowed!";
	}
}