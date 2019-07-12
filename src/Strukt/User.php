<?php

namespace Strukt;

use Strukt\Contract\UserInterface;

class User implements UserInterface{

	private $username;
	private $password;
	private $token;

	public function __construct($username = null, $password = null, $token = null){

		$this->username = $username;
		$this->password = $password;
		$this->token = $token;
	}

	public function setUsername($username){

		$this->username = $username;
	}

	public function getUsername(){

		return $this->username;
	}

	public function setPassword($password){

		$this->password = $password;
	}

	public function getPassword(){

		return $this->password;
	}

	public function setToken($token){

		$this->token = $token;
	}

	public function getToken(){

		return $this->token;
	}
}