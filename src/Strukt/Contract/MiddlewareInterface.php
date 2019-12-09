<?php

namespace Strukt\Contract;

use Strukt\Http\Request;
use Strukt\Contract\ResponseInterface;

interface MiddlewareInterface{

 	public function __invoke(Request $request, ResponseInterface $response, callable $next);
}