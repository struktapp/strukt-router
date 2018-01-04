<?php

$loader = require "vendor/autoload.php";
$loader->add('Strukt', "src/");
// $loader->add('Strukt', "../strukt-commons/src");

// use Kambo\Http\Message\Environment\Environment;
// use Kambo\Http\Message\Factories\Environment\ServerRequestFactory;
// use Kambo\Http\Message\Stream;

// use Psr\Http\Message\RequestInterface;
// use Psr\Http\Message\ResponseInterface;


// if(empty($_SERVER["REQUEST_SCHEME"]))
// 	$_SERVER["REQUEST_SCHEME"] = "http";

// $env = new Environment($_SERVER, fopen('php://input', 'w+'), $_POST, $_COOKIE, $_FILES);

// $servReq = (new ServerRequestFactory())->create($env);

// $registry = Strukt\Core\Registry::getInstance();

// $registry->set("servReq", $servReq);

//Dependency Injection
// foreach(["Ok"=>200,
// 		"Redirected"=>302] as $msg=>$code)
// 	$registry->set(sprintf("Response.%s", $msg), new Strukt\Event\Single(function() use($code){

// 		return new \Kambo\Http\Message\Response($code);
// 	}));

// foreach(["NotFound"=>404,
// 	 	"MethodNotFound"=>405,
// 	 	"Forbidden"=>403,
// 		"ServerError"=>500] as $msg=>$code)
// 	$registry->set(sprintf("Response.%s", $msg), new Strukt\Event\Single(function() use($code){

// 		$res = new \Kambo\Http\Message\Response($code);
// 		$res->getBody()->write(\Strukt\Fs::cat(sprintf("public/errors/%d.html", $code)));

// 		return $res;
// 	}));