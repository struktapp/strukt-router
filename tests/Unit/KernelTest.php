<?php

use Strukt\Contract\AuthInterface;
use Strukt\Contract\FormInterface;
use Strukt\Contract\Http\RequestInterface;
use Strukt\Contract\Http\ResponseInterface;
use Strukt\Contract\Http\SessionInterface;
use Strukt\Contract\UserInterface;
use Strukt\Http\Request;
use Strukt\Router\Fixtures\LoginForm;
use Strukt\Router\Fixtures\TraceMiddleware;
use Strukt\Router\Router;
use Strukt\Session;
use Strukt\User;

function dispatch(Router $router, Request $request): ResponseInterface
{
    return $router->kernel()->handle($request);
}

it('dispatches a plain action into the response', function () {
    $router = new Router();
    $router->get('/', fn () => 'home');

    $response = dispatch($router, new Request('GET', '/'));

    expect($response->status())->toBe(200);
    expect($response->body())->toBe('home');
});

it('injects a typed route parameter into the action', function () {
    $router = new Router();
    $router->get('/user/{id:int}', fn (int $id) => "user[{$id}]");

    $response = dispatch($router, new Request('GET', '/user/7'));

    expect($response->status())->toBe(200);
    expect($response->body())->toBe('user[7]');
});

it('returns 404 when a typed parameter does not match', function () {
    $router = new Router();
    $router->get('/user/{id:int}', fn (int $id) => "user[{$id}]");

    $response = dispatch($router, new Request('GET', '/user/abc'));

    expect($response->status())->toBe(404);
});

it('encodes an array action result as JSON', function () {
    $router = new Router();
    $router->get('/user', fn () => ['name' => 'Ada']);

    $response = dispatch($router, new Request('GET', '/user'));

    expect($response->header('Content-Type'))->toBe('application/json');
    expect(json_decode($response->body(), true))->toBe(['name' => 'Ada']);
});

it('injects the request into the action', function () {
    $router = new Router();
    $router->get('/echo', fn (RequestInterface $request) => 'q=' . $request->input('q'));

    $response = dispatch($router, new Request('GET', '/echo?q=pest'));

    expect($response->body())->toBe('q=pest');
    expect($response->status())->toBe(200);
});

it('returns 404 when no route matches', function () {
    $router = new Router();
    $router->get('/home', fn () => 'home');

    $response = dispatch($router, new Request('GET', '/missing'));

    expect($response->status())->toBe(404);
    expect($response->body())->toBe('Not found!');
});

it('returns 405 with the allowed methods when the method does not match', function () {
    $router = new Router();
    $router->get('/submit', fn () => 'done');

    $response = dispatch($router, new Request('POST', '/submit'));

    expect($response->status())->toBe(405);
    expect($response->header('Allow'))->toBe('GET');
    expect($response->body())->toBe('Method not allowed!');
});

it('runs a route middleware closure around the action', function () {
    $router = new Router();
    $router->get('/armed', fn () => 'ok')
        ->middleware(
            fn (RequestInterface $request, ResponseInterface $response, callable $next) => $next(
                $request,
                $response->setHeader('X-Arm', 'yes'),
            ),
        );

    $response = dispatch($router, new Request('GET', '/armed'));

    expect($response->header('X-Arm'))->toBe('yes');
    expect($response->body())->toBe('ok');
});

it('runs global middleware before route middleware', function () {
    TraceMiddleware::$log = [];

    $router = new Router();
    $router->middleware(TraceMiddleware::class);
    $router->get('/trace', fn () => 'ok')->middleware(TraceMiddleware::class);

    dispatch($router, new Request('GET', '/trace'));

    expect(TraceMiddleware::$log)->toBe([
        'before-/trace',
        'before-/trace',
        'after-/trace',
        'after-/trace',
    ]);
});

it('attaches the session store through the named sess middleware', function () {
    $router = new Router();
    $router->session(fn (): SessionInterface => new Session(['foo' => 'bar']));
    $router->get('/attached', fn (RequestInterface $request) => $request->session() !== null ? 'attached' : 'detached')
        ->middleware('sess');
    $router->get('/detached', fn (RequestInterface $request) => $request->session() !== null ? 'attached' : 'detached');

    expect(dispatch($router, new Request('GET', '/attached'))->body())->toBe('attached');
    expect(dispatch($router, new Request('GET', '/detached'))->body())->toBe('detached');
});

it('injects the session store into the action', function () {
    $router = new Router();
    $router->session(fn (): SessionInterface => new Session(['foo' => 'bar']));
    $router->get('/sess', fn (SessionInterface $session) => $session->get('foo'));

    expect(dispatch($router, new Request('GET', '/sess'))->body())->toBe('bar');
});

it('injects the auth gate into the action', function () {
    $router = new Router();
    $router->get('/gate', fn (AuthInterface $auth) => $auth->guest() ? 'guest' : 'user');

    expect(dispatch($router, new Request('GET', '/gate'))->body())->toBe('guest');
});

it('resolves a named binding into the action', function () {
    $router = new Router();
    $router->bind('greeting', fn () => 'hello');
    $router->get('/greet', fn ($greeting) => "say {$greeting}");

    expect(dispatch($router, new Request('GET', '/greet'))->body())->toBe('say hello');
});

it('submits and validates a route form then injects it', function () {
    $router = new Router();
    $router->post('/login', fn (FormInterface $form) => 'hi ' . $form->value('email'))
        ->form(LoginForm::class);

    $response = dispatch($router, new Request('POST', '/login', [], [
        'email' => 'ada@example.com',
        'password' => 'secret123',
    ]));

    expect($response->status())->toBe(200);
    expect($response->body())->toBe('hi ada@example.com');
});

it('returns 422 with the form errors when validation fails', function () {
    $router = new Router();
    $router->post('/login', fn (FormInterface $form) => 'never')
        ->form(LoginForm::class);

    $response = dispatch($router, new Request('POST', '/login', [], [
        'email' => '',
        'password' => 'abc',
    ]));

    expect($response->status())->toBe(422);
    expect($response->header('Content-Type'))->toBe('application/json');

    $errors = json_decode($response->body(), true)['errors'];

    expect($errors)->toHaveKeys(['email', 'password']);
});

it('returns the response built by the action', function () {
    $router = new Router();
    $router->get('/resp', fn (ResponseInterface $response) => $response->setBody('via-resp'));

    expect(dispatch($router, new Request('GET', '/resp'))->body())->toBe('via-resp');
});

it('serves a download returned by the action', function () {
    $path = tempnam(sys_get_temp_dir(), 'rpt');
    file_put_contents($path, "quarterly\n");

    $router = new Router();
    $router->get('/report', fn () => download($path, 'report.txt'));

    $response = dispatch($router, new Request('GET', '/report'));

    expect($response->status())->toBe(200);
    expect($response->header('Content-Type'))->toBe('text/plain');
    expect($response->header('Content-Disposition'))->toBe('attachment; filename="report.txt"');
    expect($response->body())->toBe("quarterly\n");
});

it('returns a redirect built with the response helper', function () {
    $router = new Router();
    $router->get('/old', fn () => response()->redirect('/home'));

    $response = dispatch($router, new Request('GET', '/old'));

    expect($response->status())->toBe(302);
    expect($response->header('Location'))->toBe('/home');
});

it('returns 401 for a route requiring authentication of a guest', function () {
    $router = new Router();
    $router->session(fn (): SessionInterface => new Session());
    $router->verify(fn (SessionInterface $session): ?UserInterface => $session->get('email') !== null
        ? new User(email: $session->get('email'))
        : null);
    $router->get('/dashboard', fn (RequestInterface $request) => $request->user()?->getEmail() ?? 'guest')
        ->requiresAuth();

    $response = dispatch($router, new Request('GET', '/dashboard'));

    expect($response->status())->toBe(401);
    expect($response->body())->toBe('Authentication required!');
});

it('binds the authenticated user to the request', function () {
    $router = new Router();
    $router->session(fn (): SessionInterface => new Session(['email' => 'ada@example.com']));
    $router->verify(fn (SessionInterface $session): ?UserInterface => new User(email: $session->get('email')));
    $router->get('/dashboard', fn (RequestInterface $request) => $request->user()?->getEmail() ?? 'guest')
        ->requiresAuth();

    $response = dispatch($router, new Request('GET', '/dashboard'));

    expect($response->status())->toBe(200);
    expect($response->body())->toBe('ada@example.com');
});

it('denies a user who lacks the required permission with 403', function () {
    $router = new Router();
    $router->session(fn (): SessionInterface => new Session(['email' => 'user@example.com']));
    $router->verify(fn (SessionInterface $session): ?UserInterface => new User(email: $session->get('email')));
    $router->permissions(fn (?UserInterface $user): array => $user?->getEmail() === 'admin@example.com' ? ['admin'] : []);
    $router->get('/admin', fn (RequestInterface $request) => $request->user()?->getEmail() ?? 'guest')
        ->requiresAuth()
        ->permissions(['admin']);

    $response = dispatch($router, new Request('GET', '/admin'));

    expect($response->status())->toBe(403);
    expect($response->body())->toBe('Unauthorized access!');
});

it('allows a user who holds the required permission', function () {
    $router = new Router();
    $router->session(fn (): SessionInterface => new Session(['email' => 'admin@example.com']));
    $router->verify(fn (SessionInterface $session): ?UserInterface => new User(email: $session->get('email')));
    $router->permissions(fn (?UserInterface $user): array => $user?->getEmail() === 'admin@example.com' ? ['admin'] : []);
    $router->get('/admin', fn (RequestInterface $request) => $request->user()?->getEmail() ?? 'guest')
        ->requiresAuth()
        ->permissions(['admin']);

    $response = dispatch($router, new Request('GET', '/admin'));

    expect($response->status())->toBe(200);
    expect($response->body())->toBe('admin@example.com');
});

it('maps an unresolvable action argument to a 500', function () {
    $router = new Router();
    $router->get('/crash', fn ($config) => 'never');

    $response = dispatch($router, new Request('GET', '/crash'));

    expect($response->status())->toBe(500);
    expect($response->body())->toContain('Unable to resolve parameter');
});