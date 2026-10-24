<?php

use Strukt\Router\Exception\MethodNotAllowedException;
use Strukt\Router\Exception\RouteNotFoundException;
use Strukt\Router\Router;

it('registers routes for every verb', function () {
    $router = new Router();

    $router->get('/get', fn () => 'get');
    $router->post('/post', fn () => 'post');
    $router->put('/put', fn () => 'put');
    $router->patch('/patch', fn () => 'patch');
    $router->delete('/delete', fn () => 'delete');
    $router->options('/options', fn () => 'options');
    $router->head('/head', fn () => 'head');

    expect($router->collection()->count())->toBe(7);
});

it('normalizes route methods', function () {
    $router = new Router();
    $route = $router->get('/hello', fn () => 'hello');

    expect($route->methods())->toBe(['GET']);
});

it('registers an any() route for every method', function () {
    $router = new Router();
    $route = $router->any('/any', fn () => 'any');

    expect($route->methods())->toBe(['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS', 'HEAD']);
});

it('matches a route with a typed parameter', function () {
    $router = new Router();
    $router->get('/user/{id:int}', fn (int $id) => "user[{$id}]");

    $match = $router->match('/user/7', 'GET');

    expect($match['params'])->toBe(['id' => 7]);
});

it('throws when no route matches the path', function () {
    (new Router())->match('/missing', 'GET');
})->throws(RouteNotFoundException::class);

it('throws with allowed methods when the method does not match', function () {
    $router = new Router();
    $router->get('/hello', fn () => 'hello');

    $router->match('/hello', 'POST');
})->throws(MethodNotAllowedException::class);

it('generates a URI from a named route', function () {
    $router = new Router();
    $router->get('/user/{id:int}', fn (int $id) => "user[{$id}]")->name('user.show');

    expect($router->path('user.show', ['id' => 7]))->toBe('/user/7');
});

it('throws when reverse generation targets an unknown name', function () {
    (new Router())->path('missing.route');
})->throws(Strukt\Router\Exception\ResolutionException::class);

it('applies a prefix inside a group', function () {
    $router = new Router();

    $router->group('/api/v1', function (Router $router) {
        $router->get('/users', fn () => 'list')->name('api.users');
    });

    expect($router->path('api.users'))->toBe('/api/v1/users');
    expect($router->match('/api/v1/users', 'GET')['route']->pattern())->toBe('/api/v1/users');
});

it('applies route defaults to reverse generation', function () {
    $router = new Router();
    $router->get('/blog/{year:int}/{?month:int}', fn (int $year, ?int $month = null) => '')
        ->name('blog.show')
        ->defaults(['month' => 1]);

    expect($router->path('blog.show', ['year' => 2026]))->toBe('/blog/2026/1');
});

it('applies route options to the registered route', function () {
    $router = new Router();

    $route = $router->get('/admin', fn () => 'admin');

    expect($route->getMiddleware())->toBe([]);
    expect($route->authRequired())->toBeFalse();
    expect($route->getPermissions())->toBe([]);

    $route->middleware(Strukt\Router\Fixtures\TraceMiddleware::class)->requiresAuth()->permissions(['admin']);

    expect($route->getMiddleware())->toBe([Strukt\Router\Fixtures\TraceMiddleware::class]);
    expect($route->authRequired())->toBeTrue();
    expect($route->getPermissions())->toBe(['admin']);
});