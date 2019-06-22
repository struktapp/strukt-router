<?php

namespace Strukt\Router\Middleware;

use Strukt\Http\Response;
use Strukt\Http\Request;

interface MiddlewareInterface{

 	public function __invoke(Request $request, Response $response, callable $next);
}