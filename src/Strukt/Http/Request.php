<?php

namespace Strukt\Http;

use Strukt\Contract\UserInterface;

use Symfony\Component\HttpFoundation\Request as SymfonyRequest; 

class Request extends SymfonyRequest{

	private $user = null;

	public function setUser(UserInterface $user = null){

		$this->user = $user;
	}

	public function getUser(){

		return $this->user;
	}
}