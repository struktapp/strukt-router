<?php

require "vendor/autoload.php";

use Strukt\Http\Request;
use Strukt\Http\Response\Plain as Response;
use Strukt\User;
use Strukt\Auth;

// $app = new Strukt\Router\QuickStart(["permissions"=>["user_add"]]);
$app = new Strukt\Router\QuickStart();

$app->get("/", function(Request $request){

	return "Hello World!";
});

$app->post("/login", function(Request $request){

	$username = $request->get("username");
	$password = $request->get("password");

	new Auth($username);
	// new Auth($username, "user_type:admin");

	return new Response(sprintf("User %s logged in.", $username));
});

$app->get("/current/user", function(Request $request){

	$user = $request->getUser();
	if(is_null($user))
		return new Response("No one is logged in!");

	return new Response($user->getUsername());
	// return new Response(sprintf("%s %s", $user->getUsername(), $user->getToken()));
});

$app->get("/logout", function(Request $request){

	$request->getSession()->invalidate();

	return new Response("User logged out.");
});

$app->get("/user/add", function(Request $request){

	return "Not Implemented!";

},"user_add");

$app->run();