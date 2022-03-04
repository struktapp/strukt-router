<?php

namespace App\Controller;

use Strukt\Http\Response\Plain as Response;
use Strukt\Http\Request;

class FooController{

	public function __construct(){

		//
	}

	public function run(Request $request){

 		return new Response('Foo is cool!', 200);
 	}
}