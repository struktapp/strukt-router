<?php

namespace Strukt\Http;

use Strukt\Contract\Http\ResponseInterface;

/**
 * Outbound HTTP response satisfying the response contract.
 *
 * Headers are kept as lists so a single name may appear more than once, as
 * with `Set-Cookie`. Building is fluent and the payload is only emitted once
 * {@see send()} runs.
 */
class Response implements ResponseInterface
{
    /**
     * HTTP status code.
     */
    private int $status = 200;

    /**
     * Header values keyed by lowercased name.
     *
     * @var array<string, list<string>>
     */
    private array $headers = [];

    /**
     * Body bytes.
     */
    private string $bodyContent = '';

    public function status(): int
    {
        return $this->status;
    }

    public function setStatus(int $status): static
    {
        if ($status < 100 || $status > 599) {
            throw new \InvalidArgumentException("Status code [{$status}] is out of range.");
        }

        $this->status = $status;

        return $this;
    }

    public function setHeader(string $name, string $value): static
    {
        if ($name === '') {
            throw new \InvalidArgumentException('Header name cannot be empty.');
        }

        $this->headers[strtolower($name)] = [$value];

        return $this;
    }

    public function addHeader(string $name, string $value): static
    {
        if ($name === '') {
            throw new \InvalidArgumentException('Header name cannot be empty.');
        }

        $this->headers[strtolower($name)][] = $value;

        return $this;
    }

    public function hasHeader(string $name): bool
    {
        return array_key_exists(strtolower($name), $this->headers);
    }

    public function header(?string $name = null, ?string $default = null): string|array|null
    {
        if ($name === null) {
            return $this->headers;
        }

        $values = $this->headers[strtolower($name)] ?? null;

        return $values === null ? $default : implode(', ', $values);
    }

    public function removeHeader(string $name): static
    {
        unset($this->headers[strtolower($name)]);

        return $this;
    }

    public function body(): string
    {
        return $this->bodyContent;
    }

    public function setBody(string $body): static
    {
        $this->bodyContent = $body;

        return $this;
    }

    public function setJson(mixed $data): static
    {
        $payload = json_encode($data, JSON_THROW_ON_ERROR);
        $this->setBody($payload);
        $this->setHeader('Content-Type', 'application/json');

        return $this;
    }

    public function send(): void
    {
        if (!headers_sent()) {
            http_response_code($this->status);
            foreach ($this->headers as $name => $values) {
                foreach ($values as $value) {
                    header("{$name}: {$value}");
                }
            }
        }

        echo $this->bodyContent;
    }
}