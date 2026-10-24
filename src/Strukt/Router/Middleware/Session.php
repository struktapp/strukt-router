<?php

namespace Strukt\Router\Middleware;

use Strukt\Contract\Http\RequestInterface;
use Strukt\Contract\Http\ResponseInterface;
use Strukt\Contract\Http\SessionInterface;
use Strukt\Contract\MiddlewareInterface;
use Strukt\Http\Request;

/**
 * Attaches the session store to the concrete request.
 *
 * The contract request has no session concept; a {@see Request} gains one here
 * so later middleware and actions can read the shared store.
 */
final class Session implements MiddlewareInterface
{
    /**
     * @param SessionInterface $session Store to attach.
     */
    public function __construct(private SessionInterface $session)
    {
    }

    public function process(RequestInterface $request, ResponseInterface $response, callable $next): ResponseInterface
    {
        if ($request instanceof Request) {
            $request->setSession($this->session);
        }

        return $next($request, $response);
    }
}