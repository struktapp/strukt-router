<?php

ini_set('display_errors', '1');
ini_set("date.timezone", "Africa/Nairobi");

error_reporting(E_ALL & ~E_STRICT & ~E_NOTICE & ~E_WARNING & ~E_DEPRECATED);

require "bootstrap.php";

use Kambo\Http\Message\Environment\Environment;
use Kambo\Http\Message\Factories\Environment\ServerRequestFactory;
use Kambo\Http\Message\Stream;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;


if(empty($_SERVER["REQUEST_SCHEME"]))
	$_SERVER["REQUEST_SCHEME"] = "http";

$env = new Environment($_SERVER, fopen('php://input', 'w+'), $_POST, $_COOKIE, $_FILES);

$servReq = (new ServerRequestFactory())->create($env);

$registry = Strukt\Core\Registry::getInstance();

$registry->set("servReq", $servReq);

//Dependency Injection
foreach(["Ok"=>200,
		"Redirected"=>302] as $msg=>$code)
	$registry->set(sprintf("Response.%s", $msg), new Strukt\Event\Single(function() use($code){

		return new \Kambo\Http\Message\Response($code);
	}));

foreach(["NotFound"=>404,
	 	"MethodNotFound"=>405,
	 	"Forbidden"=>403,
		"ServerError"=>500] as $msg=>$code)
	$registry->set(sprintf("Response.%s", $msg), new Strukt\Event\Single(function() use($code){

		$res = new \Kambo\Http\Message\Response($code);
		$res->getBody()->write(\Strukt\Fs::cat(sprintf("public/errors/%d.html", $code)));

		return $res;
	}));

// $allowed = array("user_del");

$r = new Strukt\Router\Router($servReq, $allowed);

$r->before(function() use ($registry){

	$path = $registry->get("servReq")->getUri()->getPath();

	if($path == "/"){

		$resp = $registry->get("Response.Redirected")->exec();

		$resp = $resp->withStatus(200)->withHeader('Location', '/hello/friend');
		
		Strukt\Router\Router::emit($resp);
	}
});

$r->get("/", function(){

	return "Hello World";
});

$r->get("/hello/{to:alpha}", function($to){

	return "Hello $to";
});

$r->post("/login", function(){

	return "khasdkhask";
});

$r->delete("/user/delete/{id:int}", function($id){

	return "user {$id} deleted!";

}, "user_del");

$r->any("/test/{id:int}", function(RequestInterface $req, ResponseInterface $res){

	$id = (int) $req->getAttribute('id');
    $res->getBody()->write("You asked for blog entry {$id}.");

    return $res;

	// print_r($req->getUploadedFiles());
});

// echo $r->dispatch("/hello/sam");
// echo $r->dispatch("/");
$r->run();