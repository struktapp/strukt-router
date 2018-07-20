<?php

$loader = require "vendor/autoload.php";
$loader->add('Strukt', "src/");

$servReq = Zend\Diactoros\ServerRequestFactory::fromGlobals(

    $_SERVER,
    $_GET,
    $_POST,
    $_COOKIE,
    $_FILES
);

$servReq = $servReq->withParsedBody(new Zend\Diactoros\PhpInputStream());

$registry = Strukt\Core\Registry::getInstance();
$registry->set("_staticDir", __DIR__."/public/static");
$registry->set("servReq", $servReq);

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

		$res = new Zend\Diactoros\Response();
		$res = $res->withStatus($code);
		$res->getBody()->write($body);

		return $res;
	}));