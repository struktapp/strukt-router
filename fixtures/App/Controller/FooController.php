<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;

class FooController{

	public function run(Request $request){

 		return new Response('Foo is cool!', 200);
 	}
}