<?php

require __DIR__.'/../vendor/autoload.php';

use Strukt\Http\Request;
use Strukt\Router\Router;

// Declare one action per verb method. `any()` accepts every common method.
$router = new Router();

$router->get('/', fn () => 'home');
$router->post('/ping', fn () => 'pong');
$router->any('/hook', fn () => 'any method');
$router->delete('/item/{id:int}', fn (int $id) => "deleted {$id}");

// The kernel matches the path and method, runs middleware, reflects the action,
// and wraps the result into a response. Nothing is emitted until send().
function dispatch(Router $router, string $method, string $uri): void
{
    $response = $router->kernel()->handle(new Request($method, $uri));

    // Header checks return a string or null through the response contract.
    printf(
        "%-6s  %-14s -> %d  %s%s\n",
        $method,
        $uri,
        $response->status(),
        $response->body(),
        $response->header('Allow') !== null ? '  (Allow: '.$response->header('Allow').')' : '',
    );
}

dispatch($router, 'GET', '/');
dispatch($router, 'POST', '/ping');
dispatch($router, 'PUT', '/hook');
dispatch($router, 'DELETE', '/item/12');
dispatch($router, 'GET', '/item/12');    // allows() fails -> 405 with Allow header
dispatch($router, 'GET', '/missing');    // no route matches -> 404