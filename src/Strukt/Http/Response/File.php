<?php

namespace Strukt\Http\Response;

use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Strukt\Contract\Http\ResponseInterface; 
use Strukt\Contract\Http\DownloadInterface; 

/**
 * @author Moderator <pitsolu@gmail.com>
 */
class File extends BinaryFileResponse implements DownloadInterface, ResponseInterface{

}

