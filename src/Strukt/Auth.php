<?php

namespace Strukt;

use Strukt\Http\Session;
use Strukt\Core\Registry;

class Auth{

	public function __construct(string $username, string $token = null){

		$registry = Registry::getSingleton();
		$session = $registry->get("@inject.session");

		$session->set("username", $username);

		if(!is_null($token))
			$session->set("user.token", $token);
	}
}