<?php

require __DIR__.'/../vendor/autoload.php';

use Strukt\Http\Request;
use Strukt\Router\Router;

// Groups share a path prefix; the callback receives the router so routes
// declared inside it are prefixed transparently.
$router = new Router();

$router->group('/api/v1', function (Router $router): void {
    $router->get('/users', fn () => 'list users')->name('api.users');
    $router->get('/users/{id:int}', fn (int $id) => "user {$id}")->name('api.users.show');
});

// defaults() fills omitted optional placeholders during reverse generation.
$router->get('/blog/{year:int}/{?month:int}', fn (int $year, ?int $month = null) => "year={$year} month=".($month ?? 'null'))
    ->name('blog.show')
    ->defaults(['month' => 1]);

// path() rebuilds a URI from a name; dispatch the rebuilt URI to prove it links up.
$uris = [
    'api.users' => $router->path('api.users'),
    'api.users.show' => $router->path('api.users.show', ['id' => 42]),
    'blog.show' => $router->path('blog.show', ['year' => 2026]),
    'blog.show (month set)' => $router->path('blog.show', ['year' => 2026, 'month' => 7]),
];

foreach ($uris as $label => $uri) {
    printf("%-20s = %s\n", $label, $uri);
}

echo "\nDispatch the generated URIs:\n";
foreach ($uris as $uri) {
    $response = $router->kernel()->handle(new Request('GET', $uri));
    printf("  %-14s -> %d  %s\n", $uri, $response->status(), $response->body());
}