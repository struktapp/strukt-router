<?php

namespace Strukt\Router\Middleware;

use Strukt\Contract\Http\RequestInterface;
use Strukt\Contract\Http\ResponseInterface;
use Strukt\Contract\MiddlewareInterface;
use Strukt\Contract\UserInterface;
use Strukt\Router\Exception\AuthorizationException;

/**
 * Guards a route against users lacking the required permissions.
 *
 * The permission resolver receives the bound user or null and returns the
 * granted permission names. When the required list intersects nothing the
 * chain stops with a 401 for a guest and a 403 for a denied user.
 */
final class Authorization implements MiddlewareInterface
{
    /**
     * @var \Closure
     */
    private \Closure $resolver;

    /**
     * @param callable $resolver Resolver `fn(?UserInterface): list<string>` of granted permissions.
     * @param list<string> $require Permission names required to proceed.
     */
    public function __construct(callable $resolver, private array $require = [])
    {
        $this->resolver = \Closure::fromCallable($resolver);
    }

    public function process(RequestInterface $request, ResponseInterface $response, callable $next): ResponseInterface
    {
        $require = $this->require;
        if ($require === []) {
            return $next($request, $response);
        }

        $user = $request->user();
        $granted = ($this->resolver)($user);

        if (count(array_intersect($require, $granted)) === 0) {
            throw new AuthorizationException(
                $user === null ? 'Authentication required!' : 'Unauthorized access!',
                $user === null ? 401 : 403,
            );
        }

        return $next($request, $response);
    }
}