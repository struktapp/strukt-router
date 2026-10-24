<?php

use Strukt\Container\Container;
use Strukt\Contract\AuthInterface;
use Strukt\Contract\Http\RequestInterface;
use Strukt\Contract\Http\ResponseInterface;
use Strukt\Contract\Http\SessionInterface;
use Strukt\Contract\MiddlewareInterface;
use Strukt\Http\Request;
use Strukt\Http\Response;
use Strukt\Router\Router;
use Strukt\Session;

interface ContainerClock
{
    public function now(): string;
}

final class FixedClock implements ContainerClock
{
    public function now(): string
    {
        return '2026-01-01';
    }
}

final class ClockMiddleware implements MiddlewareInterface
{
    public function __construct(private ContainerClock $clock)
    {
    }

    public function process(RequestInterface $request, ResponseInterface $response, callable $next): ResponseInterface
    {
        return $next($request, $response->setHeader('X-Now', $this->clock->now()));
    }
}

it('resolves an action parameter from the container', function () {
    $container = new Container();
    $container->set(ContainerClock::class, static fn (): ContainerClock => new FixedClock());

    $router = new Router($container);
    $router->get('/now', fn (ContainerClock $clock): string => $clock->now());

    $response = $router->kernel()->handle(new Request('GET', '/now'));

    expect($response->body())->toBe('2026-01-01');
});

it('resolves a middleware dependency from the container', function () {
    $container = new Container();
    $container->set(ContainerClock::class, static fn (): ContainerClock => new FixedClock());

    $router = new Router($container);
    $router->middleware(ClockMiddleware::class);
    $router->get('/stamped', fn (): string => 'ok');

    $response = $router->kernel()->handle(new Request('GET', '/stamped'));

    expect($response->header('X-Now'))->toBe('2026-01-01');
    expect($response->body())->toBe('ok');
});

it('mirrors bind() into the container', function () {
    $container = new Container();
    $router = new Router($container);
    $router->bind('greeting', fn (): string => 'hello');

    expect($container->bound('greeting'))->toBeTrue();
    expect($container->get('greeting'))->toBe('hello');
    expect($router->resolve('greeting'))->toBe('hello');
});

it('resolves the session from the container', function () {
    $container = new Container();
    $container->set(SessionInterface::class, static fn (): SessionInterface => new Session(['region' => 'eu-west']));

    $router = new Router($container);
    $router->get('/sess', fn (SessionInterface $session): mixed => $session->get('region'));

    $response = $router->kernel()->handle(new Request('GET', '/sess'));

    expect($response->body())->toBe('eu-west');
});

it('resolves the auth gate from the container', function () {
    $container = new Container();
    $container->set('auth', static fn (): AuthInterface => new \Strukt\Auth(new Session()));

    $router = new Router($container);
    $router->get('/gate', fn (AuthInterface $auth): string => $auth->guest() ? 'guest' : 'user');

    $response = $router->kernel()->handle(new Request('GET', '/gate'));

    expect($response->body())->toBe('guest');
});

it('publishes the resolved session and auth gate into the container', function () {
    $container = new Container();

    $router = new Router($container);
    $router->session(fn (): SessionInterface => new Session(['foo' => 'bar']));
    $router->get('/', fn (): string => 'home');

    $router->kernel()->handle(new Request('GET', '/'));

    expect($container->bound(SessionInterface::class))->toBeTrue()
        ->and($container->bound(AuthInterface::class))->toBeTrue()
        ->and($container->get(SessionInterface::class))->toBeInstanceOf(SessionInterface::class);
});
