<?php

require "bootstrap.php";

// print_r($_SERVER);
// exit;

// Kambo\Http\Message
use Kambo\Http\Message\Environment\Environment;
use Kambo\Http\Message\Factories\Environment\ServerRequestFactory;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

if(empty($_SERVER["REQUEST_SCHEME"]))
	$_SERVER["REQUEST_SCHEME"] = "http";

$env = new Environment($_SERVER, fopen('php://input', 'w+'), $_POST, $_COOKIE, $_FILES);

$servReq = (new ServerRequestFactory())->create($env);


$r = new Strukt\Router\Router($servReq);

$r->get("/", function(){

	return "Hello World";
});

$r->get("/hello/{to:alpha}", function($to){

	return "Hello $to";
});

$r->any("/test/{id:int}", function(RequestInterface $req, ResponseInterface $res){

	$id = (int) $req->getAttribute('id');
    $res->getBody()->write("You asked for blog entry {$id}.");
    return $res->getBody();
});

// echo $r->dispatch("/hello/sam");
// echo $r->dispatch("/");
exit($r->dispatch());