<?php

// Front controller: run with `php -S localhost:8080 examples/09-web-front-controller.php`
// and open http://localhost:8080/, /user/7, or redirect from /old.
// From the CLI it simulates a few requests to show the same emission path.

require __DIR__.'/../vendor/autoload.php';

use Strukt\Contract\Http\RequestInterface;
use Strukt\Http\Request;
use Strukt\Router\Router;

$router = new Router();

$router->get('/', fn () => 'it works! visit /user/{id} or /old')->name('home');
$router->get('/user/{id:int}', fn (int $id) => ['user' => ['id' => $id, 'name' => "person {$id}"]])->name('user.show');
$router->get('/old', fn () => response()->redirect('/'))->name('legacy');
$router->get('/search', fn (RequestInterface $request) => 'query='.$request->input('q', '(none)'));

// In the browser, pick the live request up from the globals.
$router->globals(fn (): RequestInterface => Request::createFromGlobals());

if (PHP_SAPI === 'cli') {
    // Simulate a browser session by swapping the globals between dispatches.
    foreach (['/', '/user/7', '/search?q=strukt', '/old'] as $uri) {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = $uri;

        $response = $router->kernel()->handle();
        printf(
            "%-14s -> %d  %s%s\n",
            $uri,
            $response->status(),
            $response->body(),
            $response->header('Location') !== null ? '  Location: '.$response->header('Location') : '',
        );
    }

    exit(0);
}

$router->kernel()->send();