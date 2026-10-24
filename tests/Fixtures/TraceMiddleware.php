<?php

namespace Strukt\Router\Fixtures;

use Strukt\Contract\Http\RequestInterface;
use Strukt\Contract\Http\ResponseInterface;
use Strukt\Contract\MiddlewareInterface;

final class TraceMiddleware implements MiddlewareInterface
{
    /**
     * @var list<string>
     */
    public static array $log = [];

    public function process(RequestInterface $request, ResponseInterface $response, callable $next): ResponseInterface
    {
        self::$log[] = 'before-' . $request->path();
        $result = $next($request, $response);
        self::$log[] = 'after-' . $request->path();

        return $result;
    }
}