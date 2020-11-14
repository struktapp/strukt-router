<?php

namespace App\Controller;

use Strukt\Http\Response;
use Strukt\Http\Request;

class UserController{

	public function __construct(){

		//
	}

	public function login(Request $request){

		$username = $request->get("username");
		$password = $request->get("password");

		return new Response(sprintf("username:%s, password:%s", $username, $password), 200);
	}

	public function check($username){

		return new Response(sprintf("check %s", $username));
	}
}