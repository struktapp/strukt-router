<?php

namespace Payroll\AuthModule\Form;

use Strukt\Contract\FormInterface;

class UserForm implements FormInterface{
	
	/**
	* @IsEmail()
	* @IsNotEmpty()
	*/
	public $email;

	/**
	* @IsNotEmpty()
	* @IsLen(8)
	*/
	public $password;

	/**
	* @EqualTo(.password)
	* @IsNotEmpty()
	*/
	public $confirm_password;
}
