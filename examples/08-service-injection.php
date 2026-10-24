<?php

require __DIR__.'/../vendor/autoload.php';

use Strukt\Contract\AuthInterface;
use Strukt\Contract\Http\RequestInterface;
use Strukt\Contract\Http\SessionInterface;
use Strukt\Http\Request;
use Strukt\Router\Router;
use Strukt\Session;

interface Clock
{
    public function now(): string;
}

final class SystemClock implements Clock
{
    public function now(): string
    {
        return date('c');
    }
}

$router = new Router();

// bind() matches an action parameter by name: a callable becomes the value.
$router->bind('greeting', fn (): string => 'hello from the router');

// An instance is handed out as-is; the parameter name is the binding key.
$router->bind('clock', new SystemClock());

// Service factories configure the session and auth gate used across dispatch.
$router->session(fn (): SessionInterface => new Session(['region' => 'eu-west']));

$router->get('/bound', fn (string $greeting, Clock $clock) => "{$greeting} @ {$clock->now()}");
$router->get('/eff', fn (RequestInterface $request, SessionInterface $session, AuthInterface $auth) => sprintf(
    'path=%s region=%s guest=%s q=%s',
    $request->path(),
    $session->get('region'),
    $auth->guest() ? 'yes' : 'no',
    $request->input('q', 'n/a'),
));

// globals() supplies the request when the kernel receives none, which makes the
// router usable in a CLI process or a test without touching $_SERVER.
$router->globals(fn (): RequestInterface => new Request('GET', '/bound'));

// An argument the kernel cannot resolve fails the dispatch with a 500.
$router->get('/crash', fn ($config) => 'never');

$router->get('/injected', fn (SessionInterface $session) => 'region='.$session->get('region'));

function dispatch(Router $router, string $method, string $uri, array $query = []): void
{
    $response = $router->kernel()->handle(new Request($method, $uri.'?'.http_build_query($query)));
    printf("%-28s -> %d  %s\n", $uri, $response->status(), $response->body());
}

dispatch($router, 'GET', '/bound');
dispatch($router, 'GET', '/eff', ['q' => 'pest']);
dispatch($router, 'GET', '/injected');
dispatch($router, 'GET', '/crash');

// handle() without an explicit request uses the globals factory.
echo "\nglobals() fallback:\n";
$response = $router->kernel()->handle();
printf("  handled '/bound'           -> %d  %s\n", $response->status(), $response->body());