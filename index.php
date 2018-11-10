<?php

ini_set('display_errors', '1');
ini_set("date.timezone", "Africa/Nairobi");

error_reporting(E_ALL & ~E_STRICT & ~E_NOTICE & ~E_WARNING & ~E_DEPRECATED);

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;

require "bootstrap.php";

$allowed = array("user_del");

$r = new Strukt\Router\Router($allowed);

$r->before(function(Request $req, Response $res) use ($registry){

	if($path == "/"){

		$res = $res->withStatus(200)->withHeader('Location', '/hello/friend');

		Strukt\Router\Router::emit($res);
	}
});

$r->get("/", function(Response $res){

	$res->setContent(Strukt\Fs::cat("public/static/index.html"));

	return $res;
});

$r->get("/hello/{to:alpha}", function($to, Request $req, Response $res){

	$res->setContent("Hello $to");

	return $res;
});

$r->post("/change/password", function() use ($servReq){

	return "Not yet implemented!";
});

$r->delete("/user/delete/{id:int}", function($id){

	return "user {$id} deleted!";

}, "user_del");

$r->try("GET", "/test/{id:int}", function(Request $req, Response $res){

	$id = (int)$req->query->get('id');

    $res->setContent("You asked for blog entry {$id}.");

    return $res;
});

$r->post("/test/json", function(Request $req, Response $res){

    return json_encode($req->getContent());
});

$r->post("/test/reqpar", function(Request $req, Response $res){

    echo $req->query->get("name");
    echo " ";
    echo $req->query->get("dept");

    return "";
});

$r->try("GET", "/login/{username:alpha}", function(Request $req, Response $res){

	$username = $req->query->get('username');
	$password = $req->query->get('password');

	$digest = sha1($username.$password);

	// print_r(array(array($username, $password),
	// 				$digest,
	// 				sha1("paulp@55w0rd")));

    $res->setContent($digest);

    return $res;
});

// $rs = $r->getRoutes();
// $ru = $rs->getRouteByUrl("/test/json");

// print_r($ru);

// echo $r->dispatch("/hello/sam");
// echo $r->dispatch("/");

// echo $r->dispatch('/login/paul', 'POST');

$r->run();