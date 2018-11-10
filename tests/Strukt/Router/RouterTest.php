<?php

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;

use Symfony\Component\HttpFoundation\ParameterBag;

use PHPUnit\Framework\TestCase;

class RouterTest extends TestCase{

	private $router;

	public function setUp(){

		$registry = Strukt\Core\Registry::getInstance();

		foreach(["Ok"=>200,"Redirected"=>302] as $msg=>$code)
			if(!$registry->exists(sprintf("Response.%s", $msg)))
				$registry->set(sprintf("Response.%s", $msg), new Strukt\Event\Event(function() use($code){

					$res = new Response();
					$res = $res->setStatusCode($code);

					return $res;
				}));

		foreach(["NotFound"=>404,
				 	"MethodNotFound"=>405,
				 	"Forbidden"=>403,
					"ServerError"=>500] as $msg=>$code)
			if(!$registry->exists(sprintf("Response.%s", $msg)))
				$registry->set(sprintf("Response.%s", $msg), new Strukt\Event\Event(function() use($code){

					$res = new Response();
					$res = $res->setStatusCode($code);
					$res->setContent(\Strukt\Fs::cat(sprintf("public/errors/%d.html", $code)));

					return $res;
				}));

		$request = new Request();
		$request->query->set('username', 'paul');
		$request->query->set('password', 'p@55w0rd');

		$this->router = new Strukt\Router\Router($allowed = array(), $request);

		$this->router->get("/", function(){

			return "Hello World";
		});

		$this->router->try("POST", "/login/{username:alpha}", function(Request $req, Response $res){

			$username = $req->query->get('username');
			$password = $req->query->get('password');

			$digest = sha1($username.$password);

		    $res->setContent($digest);

		    return $res;
		});
	}

	public function testIndexRoute(){

		$resp = $this->router->dispatch('/', 'GET');

		$this->assertEquals("Hello World", $resp->getContent());
	}

	public function testReqRes(){

		$resp = $this->router->dispatch('/login/paul', 'POST');

		$this->assertEquals($resp->getContent(), sha1("paulp@55w0rd"));
	}
}