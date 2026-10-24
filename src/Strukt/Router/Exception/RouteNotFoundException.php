<?php

namespace Strukt\Router\Exception;

/**
 * Thrown when no route matches the request path.
 */
class RouteNotFoundException extends \RuntimeException
{
    /**
     * @param string $message Error message.
     */
    public function __construct(string $message = 'Not found!', int $code = 404, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}