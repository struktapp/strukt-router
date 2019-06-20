<?php

namespace Strukt\Router\Middleware;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;

interface MiddlewareInterface{

 	public function __invoke(Request $request, Response $response, callable $next);
}