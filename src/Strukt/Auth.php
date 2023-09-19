<?php

namespace Strukt;

use Strukt\Http\Session;

class Auth{

	public function __construct(string $username, string $token = null){

		$session = reg("@inject.session")->exec();

		$session->set("username", $username);

		if(!is_null($token))
			$session->set("user.token", $token);
	}
}