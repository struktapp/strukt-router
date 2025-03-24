<?php

namespace Strukt;

use Strukt\Contract\UserInterface;

/**
* @author Moderator <pitsolu@gmail.com>
*/
class User implements UserInterface{

	private $username;
	private $password;
	private $token;

	/**
	 * @param string $username
	 * @param string $password
	 * @param string $token
	 */
	public function __construct(?string $username = null, ?string $password = null, ?string $token = null){

		$this->username = $username;
		$this->password = $password;
		$this->token = $token;
	}

	/**
	 * @param string|null $username
	 * 
	 * @return void
	 */
	public function setUsername(?string $username):void{

		$this->username = $username;
	}

	/**
	 * @return string
	 */
	public function getUsername():string{

		return $this->username;
	}

	/**
	 * @param string $password
	 * 
	 * @return void
	 */
	public function setPassword(string $password):void{

		$this->password = $password;
	}

	/**
	 * @return string
	 */
	public function getPassword():string{

		return $this->password;
	}

	/**
	 * @param string $token
	 * 
	 * @return void
	 */
	public function setToken(string $token):void{

		$this->token = $token;
	}

	/**
	 * @return string
	 */
	public function getToken():string{

		return $this->token;
	}
}