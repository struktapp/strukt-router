<?php

namespace Strukt\Router\Exception;

/**
 * Thrown when a route requires a user, or a required permission is missing.
 */
class AuthorizationException extends \RuntimeException
{
    /**
     * @param string $message Error message.
     * @param int $code Status code, 401 for a guest and 403 for a denied user.
     */
    public function __construct(string $message = 'Unauthorized access!', int $code = 401)
    {
        parent::__construct($message, $code);
    }
}