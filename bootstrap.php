<?php

$loader = require "vendor/autoload.php";
$loader->add('Strukt', "src/");

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;

$request = Request::createFromGlobals();

$request = new Request(
    $_GET,
    $_POST,
    array(),
    $_COOKIE,
    $_FILES,
    $_SERVER
);

$registry = Strukt\Core\Registry::getInstance();
$registry->set("_staticDir", __DIR__."/public/static");
$registry->set("request", $request);

//Dependency Injection
foreach(["NotFound"=>404, 
			"MethodNotFound"=>405,
		 	"Forbidden"=>403, 
		 	"ServerError"=>500,
			"Ok"=>200, 
			"Redirected"=>302,
			"NoContent"=>204] as $msg=>$code)
	$registry->set(sprintf("Response.%s", $msg), new Strukt\Event\Event(function() use($code){

		$body = "";
		if(in_array($code, array(403,404,405,500)))
			$body = Strukt\Fs::cat(sprintf("public/errors/%d.html", $code));

		$res = new Response(

		    $body,
		    $code,
		    array('content-type' => 'text/html')
		);

		return $res;
	}));