<?php

namespace Strukt;

/**
* @author Moderator <pitsolu@gmail.com>
*/
class Auth{

	/**
	 * @param string $username
	 * @param string $token
	 */
	public function __construct(string $username, ?string $token = null){

		$session = event("@inject.session")->exec();

		$session->set("username", $username);

		if(!is_null($token))
			$session->set("user.token", $token);
	}
}