<?php

namespace Strukt\Router\Middleware;

use Strukt\Http\Response;
use Strukt\Http\Request;
use Strukt\Router\FileFinder;
use Strukt\Core\Registry;
use Strukt\Contract\MiddlewareInterface;
use Strukt\Contract\AbstractMiddleware;


class StaticFileFinder extends AbstractMiddleware implements MiddlewareInterface{

	private $finder;

	public function __construct(string $root_dir, string $rel_dir){

		$this->finder = new FileFinder($root_dir, $rel_dir);

		$this->core()->set("filefinder.static", $this->finder);
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