<?php

namespace Strukt\Router\Middleware;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Request;
use Strukt\Router\FileFinder;
use Strukt\Core\Registry;

class StaticFileFinder implements MiddlewareInterface{

	private $finder;

	public function __construct(string $root_dir, string $rel_dir){

		$this->finder = new FileFinder($root_dir, $rel_dir);

		Registry::getInstance()->set("filefinder.static", $this->finder);
	}

	public function __invoke(Request $request, Response $response, callable $next){

		$uri = $request->getRequestUri();

		if($this->finder->exists($uri)){

			$contents = $this->finder->getContents($uri);

			return new Response($contents, 200);
		}

		return $next($request, $response);
	}
}