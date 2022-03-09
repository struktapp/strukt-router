<?php

namespace Strukt\Http\Response;

use Symfony\Component\HttpFoundation\RedirectResponse as NativeRedirectResponse;
use Strukt\Contract\Http\ResponseInterface; 

class Redirect extends NativeRedirectResponse implements ResponseInterface{

	//
}