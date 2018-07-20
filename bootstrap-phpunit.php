<?php

// use Kambo\Http\Message\Environment\Environment;
// use Kambo\Http\Message\Factories\Environment\ServerRequestFactory;
// use Kambo\Http\Message\Response;

// use Strukt\Core\Registry;
// use Strukt\Event\Single;
// use Strukt\Fs;

$loader = require "vendor/autoload.php";
$loader->add('Strukt', "src/");
// $loader->add('Strukt', "../strukt-commons/src");

$registry = Strukt\Core\Registry::getInstance();
$registry->set("_staticDir", __DIR__."/public/static");

// foreach(["NotFound"=>404, 
// 			"MethodNotFound"=>405,
// 		 	"Forbidden"=>403, 
// 		 	"ServerError"=>500,
// 			"Ok"=>200, 
// 			"Redirected"=>302,
// 			"NoContent"=>204] as $msg=>$code)
// 	$registry->set(sprintf("Response.%s", $msg), new Strukt\Event\Event(function() use($code){

// 		$body = "";
// 		if(in_array($code, array(403,404,405,500)))
// 			$body = Strukt\Fs::cat(sprintf("public/errors/%d.html", $code));

// 		$res = new Zend\Diactoros\Response();
// 		$res = $res->withStatus($code);
// 		$res->getBody()->write($body);

// 		return $res;
// 	}));