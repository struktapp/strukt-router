<?php

namespace Strukt\Router\Middleware;

use Strukt\Contract\AuthInterface;
use Strukt\Contract\Http\RequestInterface;
use Strukt\Contract\Http\ResponseInterface;
use Strukt\Contract\Http\SessionInterface;
use Strukt\Contract\MiddlewareInterface;
use Strukt\Contract\UserInterface;

/**
 * Resolves the current user and binds it to the request.
 *
 * When a `verify` resolver is configured it wins and reads from the session;
 * otherwise the user is hydrated from the auth gate. The bound user is
 * available to later middleware through {@see RequestInterface::user()}.
 */
final class Authentication implements MiddlewareInterface
{
    /**
     * @var \Closure|null
     */
    private ?\Closure $verify;

    /**
     * @param AuthInterface $auth Auth gate used to resolve the user.
     * @param callable|null $verify Resolver `fn(SessionInterface): ?UserInterface`, or null.
     * @param SessionInterface|null $session Store handed to the resolver, or null.
     */
    public function __construct(
        private AuthInterface $auth,
        ?callable $verify = null,
        private ?SessionInterface $session = null,
    ) {
        $this->verify = $verify === null ? null : \Closure::fromCallable($verify);
    }

    public function process(RequestInterface $request, ResponseInterface $response, callable $next): ResponseInterface
    {
        $user = $this->resolveUser();
        if ($user !== null) {
            $request->setUser($user);
        }

        return $next($request, $response);
    }

    /**
     * Resolves the current user through the verify resolver or the auth gate.
     *
     * @return UserInterface|null Current user, or null for a guest.
     */
    private function resolveUser(): ?UserInterface
    {
        if ($this->verify !== null && $this->session !== null) {
            return ($this->verify)($this->session);
        }

        return $this->auth->user();
    }
}