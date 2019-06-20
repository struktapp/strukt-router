<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;

class UserController{

	public function login(Request $request){

		$username = $request->get("username");
		$password = $request->get("password");

		return new Response(sprintf("username:%s, password:%s", $username, $password), 200);
	}

	public function check($username){

		return new Response(sprintf("check %s", $username));
	}
}