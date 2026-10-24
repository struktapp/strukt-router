<?php

namespace Strukt\Http;

/**
 * Fluent builder for typed HTTP responses.
 *
 * A builder carries a status code and a header set, then materializes one of
 * the concrete responses. The `response()` helper hands one out so actions can
 * return `response()->body(...)` or `response()->json(...)` directly.
 */
final class ResponseBuilder
{
    /**
     * @param int $code Initial status code.
     * @param array<string, string> $headers Initial header values.
     */
    public function __construct(
        private int $code = 200,
        private array $headers = [],
    ) {
    }

    /**
     * Merges additional headers into the pending response.
     *
     * @param array<string, string> $headers Header values keyed by name.
     * @return static This builder.
     */
    public function headers(array $headers): static
    {
        $this->headers = array_merge($this->headers, $headers);

        return $this;
    }

    /**
     * Builds a plain-text response.
     *
     * @param string $content Body text.
     * @return Response Plain-text response.
     */
    public function body(string $content): Response
    {
        return $this->plain($content);
    }

    /**
     * Builds a plain-text response with a text content type.
     *
     * @param string $content Body text.
     * @return Response Plain-text response.
     */
    public function plain(string $content): Response
    {
        $response = new Response();
        $response->setStatus($this->code)->setBody($content);

        foreach ($this->headers as $name => $value) {
            $response->setHeader($name, $value);
        }

        if (!$response->hasHeader('Content-Type')) {
            $response->setHeader('Content-Type', 'text/plain; charset=utf-8');
        }

        return $response;
    }

    /**
     * Builds a JSON response.
     *
     * @param mixed $data Value to encode.
     * @return Response JSON response.
     */
    public function json(mixed $data): Response
    {
        $response = new Response();
        $response->setStatus($this->code)->setJson($data);

        foreach ($this->headers as $name => $value) {
            $response->setHeader($name, $value);
        }

        return $response;
    }

    /**
     * Builds a redirect response.
     *
     * @param string $url Destination URL.
     * @param int $status Redirect status, usually 301 or 302.
     * @return Response Redirect response.
     */
    public function redirect(string $url, int $status = 302): Response
    {
        $response = new Response();
        $response->setStatus($status)->setHeader('Location', $url);

        foreach ($this->headers as $name => $value) {
            $response->setHeader($name, $value);
        }

        return $response;
    }
}