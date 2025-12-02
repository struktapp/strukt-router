<?php

namespace Payroll\AuthModule\Form;

use Strukt\Contract\FormInterface;
use Strukt\Contract\Form;

class UserForm extends Form implements FormInterface{
	
	/**
	* @IsEmail()
	* @IsNotEmpty()
	*/
	public $email;

	/**
	* @IsNotEmpty()
	*/
	public $password;

	/**
	* @EqualTo(.password)
	* @IsNotEmpty()
	*/
	public $confirm_password;
}
