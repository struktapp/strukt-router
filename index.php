<?php

// require "bootstrap.php";
require "autoload.php";

use Strukt\Http\Request;
use Strukt\Contract\Http\RequestInterface;
use Strukt\Contract\Http\ResponseInterface;

use Payroll\AuthModule\Form\UserForm;

router(globals:fn()=>Request::createFromGlobals());
router(session:fn()=>new Strukt\Http\Session\Native);
router(roles:fn(SessionInterface $session)=>[]);
router(permissions:fn(SessionInterface $session)=>[]);
router(verify:fn(SessionInterface $session)=>Strukt\User::create($session->get("username")));
router(middlewares:[

	Strukt\Router\Middleware\Session::class,
	Strukt\Router\Middleware\Authentication::class,
	Strukt\Router\Middleware\Authorization::class
]);

router()->get("/", function(){

	return "Hello World";
});

router()->get("/hello/{name}", function($name, RequestInterface $request){

	return sprintf("Hello %s!", $name);
});

router(allow:["user_view"])->post("/user/{id:int}", function(int $id, RequestInterface $request){

	return sprintf("User[%d]!", $id);
});

router(form:UserForm::class, allow:[])->post("/login", function(RequestInterface $request){

	$username = $request->get("username");
	$password = $request->get("password");

	if(!empty($username) && !empty($password)){

		if($username == "admin" && $password == "p@55w0rd"){

			$request->getSession()->set("username", $username);

			return response()->body(sprintf("User %s logged in.", $username));
		}
	}

	return response(401)->body("Unable to log in!");
});