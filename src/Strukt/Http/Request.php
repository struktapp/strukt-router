<?php

namespace Strukt\Http;

use Strukt\Contract\Http\RequestInterface;
use Strukt\Contract\Http\SessionInterface;
use Strukt\Contract\Http\UploadedFileInterface;
use Strukt\Contract\UserInterface;

/**
 * Inbound HTTP request satisfying the request contract.
 *
 * The request is constructed from plain arrays so it works under a web server
 * and in tests or CLI processes alike. {@see createFromGlobals()} captures the
 * current PHP request; custom constructor arguments give callers full control.
 */
class Request implements RequestInterface
{
    /**
     * Authenticated user bound to this request.
     */
    private ?UserInterface $user = null;

    /**
     * Session store attached by the router middleware.
     */
    private ?SessionInterface $session = null;

    /**
     * Query-string values parsed from the URI when none were supplied.
     */
    private readonly array $query;

    /**
     * @param string $method HTTP method, uppercased.
     * @param string $uri Request target including the query string.
     * @param array<string, mixed> $query Query-string values.
     * @param array<string, mixed> $body Body values, taking precedence over the query.
     * @param array<string, string> $headers Header values keyed by name.
     * @param array<string, UploadedFileInterface> $files Uploads keyed by field name.
     * @param string $content Raw request body bytes.
     */
    public function __construct(
        private readonly string $method = 'GET',
        private readonly string $uri = '/',
        array $query = [],
        private readonly array $body = [],
        private readonly array $headers = [],
        private readonly array $files = [],
        private readonly string $content = '',
    ) {
        parse_str((string) parse_url($this->uri, PHP_URL_QUERY), $parsed);
        $this->query = $query === [] && $parsed !== [] ? $parsed : $query;
    }

    /**
     * Builds a request from the current PHP globals.
     *
     * @return static Request capturing the live request.
     */
    public static function createFromGlobals(): static
    {
        $headers = [];
        foreach ($_SERVER as $name => $value) {
            if (str_starts_with($name, 'HTTP_') && is_string($value)) {
                $headers[str_replace('_', '-', substr($name, 5))] = $value;
            }
        }

        $files = [];
        foreach ($_FILES as $name => $meta) {
            if (is_array($meta) && isset($meta['name']) && is_string($meta['name'])) {
                $files[(string) $name] = new UploadedFile($meta);
            }
        }

        return new static(
            method: strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET'),
            uri: $_SERVER['REQUEST_URI'] ?? '/',
            query: $_GET,
            body: $_POST,
            headers: $headers,
            files: $files,
            content: file_get_contents('php://input') ?: '',
        );
    }

    public function method(): string
    {
        return strtoupper($this->method);
    }

    public function uri(): string
    {
        return $this->uri;
    }

    public function path(): string
    {
        return (string) (parse_url($this->uri, PHP_URL_PATH) ?: '/');
    }

    public function query(?string $key = null, mixed $default = null): mixed
    {
        if ($key === null) {
            return $this->query;
        }

        return array_key_exists($key, $this->query) ? $this->query[$key] : $default;
    }

    public function header(?string $name = null, ?string $default = null): string|array|null
    {
        if ($name === null) {
            return $this->headers;
        }

        $key = strtolower($name);
        foreach ($this->headers as $header => $value) {
            if (strtolower($header) === $key) {
                return $value;
            }
        }

        return $default;
    }

    public function hasHeader(string $name): bool
    {
        return $this->header($name) !== null;
    }

    public function body(): string
    {
        return $this->content;
    }

    public function input(?string $key = null, mixed $default = null): mixed
    {
        if ($key === null) {
            return $this->all();
        }

        if (array_key_exists($key, $this->body)) {
            return $this->body[$key];
        }

        return array_key_exists($key, $this->query) ? $this->query[$key] : $default;
    }

    public function all(): array
    {
        return array_merge($this->query, $this->body);
    }

    public function isMethod(string ...$methods): bool
    {
        return in_array($this->method(), array_map('strtoupper', $methods), true);
    }

    public function file(string $name): ?UploadedFileInterface
    {
        $file = $this->files[$name] ?? null;

        return $file instanceof UploadedFileInterface ? $file : null;
    }

    public function user(): ?UserInterface
    {
        return $this->user;
    }

    public function setUser(?UserInterface $user = null): void
    {
        $this->user = $user;
    }

    /**
     * Binds the session store to this request.
     *
     * The contract does not describe session binding, so this method is only
     * available on the concrete request; the router kernel attaches the store
     * before running the middleware pipeline.
     *
     * @param SessionInterface $session Store to attach.
     * @return static This request.
     */
    public function setSession(SessionInterface $session): static
    {
        $this->session = $session;

        return $this;
    }

    /**
     * Reads the bound session store.
     *
     * @return SessionInterface|null Attached session, or null when unset.
     */
    public function session(): ?SessionInterface
    {
        return $this->session;
    }
}