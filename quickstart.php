<?php

require "vendor/autoload.php";

use Strukt\Http\Request;
use Strukt\Http\Response;
use Strukt\User;

$app = new Strukt\Router\QuickStart(["permissions"=>"user_add"]);

$app->get("/", function(Request $request){

	return "Hello World!";
});

$app->post("/login", function(Request $request){

	$username = $request->get("username");
	$password = $request->get("password");

	$request->getSession()->set("username", $username);

	return new Response(sprintf("User %s logged in.", $username));
});

$app->get("/current/user", function(Request $request){

	$user = $request->getUser();
	if(is_null($user))
		return new Response("No one is logged in!");

	return new Response($user->getUsername());
});

$app->get("/logout", function(Request $request){

	$request->getSession()->invalidate();

	return new Response("User logged out.");
});

$app->get("/user/add", function(Request $request){

	return "Not Implemented!";

},"user_add");

$app->run();