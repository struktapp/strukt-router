<?php

namespace Strukt;

use Strukt\Contract\UserInterface;

class User implements UserInterface{

	private $username;

	public function __construct(){

		//
	}

	public function setUsername($username){

		$this->username = $username;
	}

	public function getUsername(){

		return $this->username;
	}
}