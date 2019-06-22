<?php

namespace Strukt\Http;

use Strukt\UserInterface;

use Symfony\Component\HttpFoundation\Request as SymfonyRequest; 

class Request extends SymfonyRequest{

	private $user;

	public function setUser(UserInterface $user){

		$this->user = $user;
	}

	public function getUser(){

		return $this->user;
	}
}