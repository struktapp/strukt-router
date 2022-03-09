<?php

namespace Strukt\Http;

use Strukt\Contract\UserInterface;
use Strukt\Contract\Http\RequestInterface;

use Symfony\Component\HttpFoundation\Request as NativeRequest; 

class Request extends NativeRequest implements RequestInterface{

	private $user = null;

	public function setUser(UserInterface $user = null){

		$this->user = $user;
	}

	public function getUser(){

		return $this->user;
	}
}