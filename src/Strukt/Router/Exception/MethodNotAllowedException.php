<?php

namespace Strukt\Router\Exception;

/**
 * Thrown when the path matches a route but the request method does not.
 */
class MethodNotAllowedException extends \RuntimeException
{
    /**
     * Methods the matched route would accept.
     *
     * @var list<string>
     */
    private array $allowedMethods;

    /**
     * @param list<string> $allowedMethods Methods the route would accept.
     * @param string $message Error message.
     */
    public function __construct(array $allowedMethods, string $message = 'Method not allowed!', ?\Throwable $previous = null)
    {
        $this->allowedMethods = $allowedMethods;

        parent::__construct($message, 405, $previous);
    }

    /**
     * Lists the methods the matched route would accept.
     *
     * @return list<string> Acceptable methods, uppercased.
     */
    public function allowedMethods(): array
    {
        return $this->allowedMethods;
    }
}