<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;

class StartpageController{

	public function run(Request $request){

 		return new Response('Welcome to index!', 200);
 	}
}