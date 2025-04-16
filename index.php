<?php

use Strukt\Http\Request;
use Strukt\Http\Session\Native as Session;
use Strukt\Contract\Http\SessionInterface;

require "vendor/autoload.php";

env("json_validation_err", true);
env("res_send_headers", true);

$app = new Strukt\Router\Kernel(Request::createFromGlobals());
$app->inject("session", function(){

	return new Strukt\Http\Session\Native;
});

$app->inject("permissions", function(SessionInterface $session){

	$permissions = []; 

	$permissions[] = "admin_only";
	if($session->has("username"))
		$permissions[] = "strukt:auth"; 
	
	return $permissions;
});

$app->inject("verify", function(Session $session){

	$user = new Strukt\User();
	$user->setUsername($session->get("username"));

	return $user;
});

$app->middlewares([

	Strukt\Router\Middleware\Session::class,
	Strukt\Router\Middleware\Authentication::class,
	Strukt\Router\Middleware\Authorization::class,
]);

$app->get("/", function(){

	return "Hello World!";
});

$app->get("/user", function(){

	return "Some User!";
	
},"strukt:auth");

$app->get("/hello/{name}", function($name, Request $request){

	return sprintf("Hello %s!", $name);
});

$app->post("/login", function(Request $request){

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

$app->post("/user/current", function(Request $request){

	$username = $request->getUser();

	if(!empty($username))
		return response()->body(sprintf("User:%s", $username));

	return response(403)->body("No user!");
});

$app->post("/logout", function(Request $request){

	$request->getSession()->invalidate();

	return response()->body("User logged out.");
});

$app->get("/secret", function(Request $request){

	return "secret accessed!";

},"admin_only");

$app->get("/test", function(Request $request){

	return response()->body("<a href='/download' download>Download</a>");
});

$app->get("/download", function(Request $request){

	return response()->file(fs("fixtures")->path("gold.pdf"), "myfile.pdf");
});

exit($app->run());