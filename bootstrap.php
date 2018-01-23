<?php

use Kambo\Http\Message\Environment\Environment;
use Kambo\Http\Message\Factories\Environment\ServerRequestFactory;
use Kambo\Http\Message\Response;

use Strukt\Core\Registry;
use Strukt\Event\Event;
use Strukt\Fs;

$loader = require "vendor/autoload.php";
$loader->add('Strukt', "src/");
// $loader->add('Strukt', "../strukt-commons/src");

$registry = Registry::getInstance();
$registry->set("_staticDir", __DIR__."/public/static");

if(empty($_SERVER["REQUEST_SCHEME"]))
	$_SERVER["REQUEST_SCHEME"] = "http";

$env = new Environment($_SERVER, fopen('php://input', 'w+'), $_POST, $_COOKIE, $_FILES);

$servReq = (new ServerRequestFactory())->create($env);

//Dependency Injection
foreach(["NotFound"=>404, 
			"MethodNotFound"=>405,
		 	"Forbidden"=>403, 
		 	"ServerError"=>500,
			"Ok"=>200, 
			"Redirected"=>302,
			"NoContent"=>204] as $msg=>$code)
	$registry->set(sprintf("Response.%s", $msg), new Event(function() use($code){

		$res = new Response($code);

		if(in_array($code, array(403,404,405,500)))
			$res->getBody()->write(Fs::cat(sprintf("public/errors/%d.html", $code)));

		return $res;
	}));