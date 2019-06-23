<?php

namespace Strukt\Contract;

use Strukt\Http\Response;
use Strukt\Http\Request;

interface MiddlewareInterface{

 	public function __invoke(Request $request, Response $response, callable $next);
}