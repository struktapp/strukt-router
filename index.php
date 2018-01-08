<?php

ini_set('display_errors', '1');
ini_set("date.timezone", "Africa/Nairobi");

error_reporting(E_ALL & ~E_STRICT & ~E_NOTICE & ~E_WARNING & ~E_DEPRECATED);

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

require "bootstrap.php";

// $allowed = array("user_del");

$r = new Strukt\Router\Router($servReq, $allowed);

$r->before(function(RequestInterface $req, ResponseInterface $res) use ($registry){

	// $path = $registry->get("servReq")->getUri()->getPath();

	$req->getUri()->getPath();

	if($path == "/"){

		// $resp = $registry->get("Response.Redirected")->exec();

		// $resp = $resp->withStatus(200)->withHeader('Location', '/hello/friend');

		$res = $res->withStatus(200)->withHeader('Location', '/hello/friend');

		Strukt\Router\Router::emit($res);
		
		// Strukt\Router\Router::emit($resp);
	}
});

$r->get("/", function(ResponseInterface $res){

	// return "Hello World";

	// $res->getBody()->write("Hello World!");
	$res->getBody()->write(Strukt\Fs::cat("public/static/index.html"));

	return $res;
});

$r->get("/hello/{to:alpha}", function($to){

	return "Hello $to";
});

$r->post("/login", function() use ($servReq){

	// return "khasdkhask";

	// $request = $registry->get("servReq");

	// $parsedBody = $servReq->getParsedBody();

	// $body = (string)$env->getBody();

	// print_r($parsedBody);
	// var_dump($parsedBody);

	print_r($_REQUEST);

	$body = $servReq->getParsedBody();

	// echo 2;

	var_dump($body);
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