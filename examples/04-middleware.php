<?php

require __DIR__.'/../vendor/autoload.php';

use Strukt\Contract\Http\RequestInterface;
use Strukt\Contract\Http\ResponseInterface;
use Strukt\Contract\Http\SessionInterface;
use Strukt\Contract\MiddlewareInterface;
use Strukt\Http\Request;
use Strukt\Router\Router;
use Strukt\Session;

// 1) A middleware object applied globally: it runs for every route.
final class TimerMiddleware implements MiddlewareInterface
{
    public function process(RequestInterface $request, ResponseInterface $response, callable $next): ResponseInterface
    {
        $start = microtime(true);
        $result = $next($request, $response);
        $result->setHeader('X-Took-Ms', sprintf('%.3f', (microtime(true) - $start) * 1000));

        return $result;
    }
}

// 2) A middleware class that needs the session store. Constructor parameters
// are resolved from the per-request services, so registering the class as a
// named middleware is enough to get SessionInterface injected.
final class SignerMiddleware implements MiddlewareInterface
{
    public function __construct(private SessionInterface $session)
    {
    }

    public function process(RequestInterface $request, ResponseInterface $response, callable $next): ResponseInterface
    {
        $result = $next($request, $response);
        $result->setHeader('X-Tenant', (string) ($this->session->get('tenant', 'root')));

        return $result;
    }
}

$router = new Router();
$router->middleware(new TimerMiddleware());

// Register a custom named middleware and route-scoped closures.
$router->session(fn (): SessionInterface => new Session(['tenant' => 'acme']));
$router->named('signed', SignerMiddleware::class);

$router->get('/hello', fn () => 'hello world')->middleware('signed');
$router->get('/armed', fn () => 'armed ok')->middleware(
    fn (RequestInterface $request, ResponseInterface $response, callable $next) => $next(
        $request,
        $response->setHeader('X-Arm', 'yes'),
    ),
);
// Left untouched by the route-scoped middleware, still timed globally.
$router->get('/plain', fn () => 'plain');

foreach (['/hello', '/armed', '/plain'] as $uri) {
    $response = $router->kernel()->handle(new Request('GET', $uri));
    printf(
        "%-8s -> %d  %s  [X-Tenant=%s X-Arm=%s X-Took-Ms=%s]\n",
        $uri,
        $response->status(),
        $response->body(),
        $response->header('X-Tenant') ?? '-',
        $response->header('X-Arm') ?? '-',
        $response->header('X-Took-Ms') ?? '-',
    );
}