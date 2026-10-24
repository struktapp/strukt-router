<?php

namespace Strukt\Router\Exception;

/**
 * Thrown when a route form fails validation.
 */
class ValidationException extends \RuntimeException
{
    /**
     * Failure messages keyed by field name.
     *
     * @var array<string, list<string>>
     */
    private array $errors;

    /**
     * @param array<string, list<string>> $errors Failure messages keyed by field name.
     * @param string $message Error message.
     */
    public function __construct(array $errors, string $message = 'Form failed validation!', ?\Throwable $previous = null)
    {
        $this->errors = $errors;

        parent::__construct($message, 422, $previous);
    }

    /**
     * Reads the recorded validation failures.
     *
     * @return array<string, list<string>> Messages keyed by field name.
     */
    public function errors(): array
    {
        return $this->errors;
    }
}