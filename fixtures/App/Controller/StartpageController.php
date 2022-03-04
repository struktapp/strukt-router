<?php

namespace App\Controller;

use Strukt\Http\Response\Plain as Response;
use Strukt\Http\Request;

class StartpageController{

	public function __construct(){

		//
	}

	public function run(Request $request){

 		return new Response('Welcome to index!', 200);
 	}
}