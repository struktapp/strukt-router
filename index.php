<?php

// require "bootstrap.php";
require "autoload.php";

use Strukt\Http\Request;
use Strukt\Contract\Http\RequestInterface;
use Strukt\Contract\Http\ResponseInterface;
use Strukt\Contract\Http\SessionInterface;
use Strukt\Contract\FormInterface;

use Payroll\AuthModule\Form\UserForm;

router(globals:fn()=>Request::createFromGlobals());
router(session:fn()=>new Strukt\Http\Session\Native);
router(roles:fn(SessionInterface $session)=>[]);
router(permissions:fn(SessionInterface $session)=>["user_view"]);
router(verify:fn(SessionInterface $session)=>new Strukt\User(email:$session->get("email")));
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

router(form:UserForm::class)->post("/login", function(RequestInterface $request, FormInterface $user){

	if($user->email == "admin@gmail.com" && $user->password == "p@55w0rd"){

		$request->getSession()->set("email", $user->email);
		return response()->body(sprintf("User %s logged in.", $user->email));
	}

	return response(401)->body("Unable to log in!");
});

router()->post("/current/user", function(RequestInterface $request){

	$email = $request->getSession()->get("email");
	return sprintf("Current user %s", $email);
});

router()->post("/user/details", function(RequestInterface $request){

	$email = $request->getSession()->get("email");
	if($email)
		return response()->json([
			"success"=>true,
			"data"=>[
				"email"=>$email,
				"role"=>"admin",
				"status"=>"active"
			]
		]);

	return response()->json([
		"success"=>false,
		"message"=>"Please login"
	]);
});

router()->post("/logout", function(RequestInterface $request){

	$request->getSession()->invalidate();
	return response()->body("User logged out.");
});

router()->get("/download", function(RequestInterface $request){

	return response()->file(file_get_contents("fixture/blank.pdf"), "null.pdf");
});