<?php

require __DIR__.'/../vendor/autoload.php';

use Strukt\Http\Request;
use Strukt\Router\Router;

// Placeholders are typed and cast before the action runs. The action declares
// the same names and types, and the router injects the matched values by name.
$router = new Router();

$router->get('/user/{id:int}', fn (int $id) => sprintf('int id=%d', $id));
$router->get('/price/{value:float}', fn (float $value) => sprintf('float value=%.2f', $value));
$router->get('/toggle/{on:bool}', fn (bool $on) => 'bool on='.var_export($on, true));
$router->get('/post/{slug}', fn (string $slug) => "slug={$slug}");
$router->get('/day/{on:date}', fn (string $on) => "date={$on}");
$router->get('/token/{id:uuid}', fn (string $id) => "uuid={$id}");
$router->get('/files/{rest:*}', fn (string $rest) => "catch={$rest}");
$router->get('/blog/{year:int}/{?month:int}', fn (int $year, ?int $month = null) => "year={$year} month=".($month ?? 'null'));

function dispatch(Router $router, string $method, string $uri): void
{
    $response = $router->kernel()->handle(new Request($method, $uri));
    printf("%-26s -> %d  %s\n", $uri, $response->status(), $response->body());
}

dispatch($router, 'GET', '/user/7');
dispatch($router, 'GET', '/user/abc');              // {id:int} rejects non-digits -> 404
dispatch($router, 'GET', '/price/19.99');
dispatch($router, 'GET', '/toggle/true');
dispatch($router, 'GET', '/post/hello-world');
dispatch($router, 'GET', '/day/2026-10-07');
dispatch($router, 'GET', '/token/6ba7b810-9dad-11d1-80b4-00c04fd430c8');
dispatch($router, 'GET', '/files/css/app/main.css'); // wildcard captures the rest
dispatch($router, 'GET', '/blog/2026');              // optional {?month:int} omitted
dispatch($router, 'GET', '/blog/2026/5');