<?php

namespace Strukt;

use Strukt\Http\Session;
use Strukt\Core\Registry;
use Strukt\Reg;

class Auth{

	public function __construct(string $username, string $token = null){

		$session = Reg::get("@inject.session")->exec();

		$session->set("username", $username);

		if(!is_null($token))
			$session->set("user.token", $token);
	}
}