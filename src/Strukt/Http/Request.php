<?php

namespace Strukt\Http;

use Strukt\Contract\UserInterface;
use Strukt\Contract\RequestInterface;

use Symfony\Component\HttpFoundation\Request as SymfonyRequest; 

class Request extends SymfonyRequest implements RequestInterface{

	private $user = null;

	public function setUser(UserInterface $user){

		$this->user = $user;
	}

	public function getUser(){

		return $this->user;
	}
}