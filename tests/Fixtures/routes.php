<?php

declare(strict_types=1);

// Bootstrap file for the route CLI tests: declares a small route map and
// returns it, exactly as an application's routes.php would.

use Strukt\Router\Router;

$router = new Router();

$router->get('/', fn () => 'home')->name('home');
$router->get('/user/{id:int}', fn (int $id) => "user {$id}")->name('user.show')->requiresAuth();
$router->any('/hook', fn () => 'hook');

$router->group('/admin', function (Router $router): void {
    $router->post('/report', fn () => 'report')->name('admin.report')->permissions(['reports.create']);
});

return $router;
