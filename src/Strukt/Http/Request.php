<?php

namespace Strukt\Http;

use Strukt\Contract\UserInterface;
use Strukt\Contract\Http\RequestInterface;

use Symfony\Component\HttpFoundation\Request as NativeRequest; 

/**
 * @author Moderator <pitsolu@gmail.com>
 */
class Request extends NativeRequest implements RequestInterface{

	private $user = null;

	/**
	 * @param \Strukt\Contract\UserInterface $user 
	 * 
	 * @return void
	 */
	public function setUser(?UserInterface $user = null):void{

		$this->user = $user;
	}

	/**
	 * @return string
	 */
	public function getUser():?string{

		return $this->user->getUsername();
	}
}