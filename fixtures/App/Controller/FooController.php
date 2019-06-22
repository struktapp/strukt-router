<?php

namespace App\Controller;

use Strukt\Http\Response;
use Strukt\Http\Request;

class FooController{

	public function run(Request $request){

 		return new Response('Foo is cool!', 200);
 	}
}