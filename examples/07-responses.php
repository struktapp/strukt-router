<?php

require __DIR__.'/../vendor/autoload.php';

use Strukt\Contract\Http\RequestInterface;
use Strukt\Contract\Http\ResponseInterface;
use Strukt\Http\Download;
use Strukt\Http\Request;
use Strukt\Router\Router;

// An action may return a string, an array, a ResponseInterface, or null.
// Strings become the body, arrays become a JSON document, and a returned
// response is used as-is.
$router = new Router();

$router->get('/text', fn () => 'plain text body');
$router->get('/json', fn () => ['ok' => true, 'items' => [1, 2, 3]]);

// The response object is injectable and can be mutated before it is wrapped.
$router->get('/mutate', fn (ResponseInterface $response) => $response
    ->setStatus(201)
    ->setHeader('X-Created', 'yes')
    ->setBody('created'));

// The response() helper builds typed responses: json, plain, redirect.
$router->get('/payload', fn () => response()->json(['ok' => true]));
$router->get('/old', fn () => response()->redirect('/new', 301));
$router->get('/csv', fn () => response()->plain("a,b,c\n1,2,3"));

// download() hands out a file response with an optional filename.
$router->get('/report', function (): Download {
    $tmp = tempnam(sys_get_temp_dir(), 'rpt');
    file_put_contents($tmp, "quarterly report\n");

    return download($tmp, 'report.txt');
});

function inspect(Router $router, string $uri): void
{
    $response = $router->kernel()->handle(new Request('GET', $uri));
    printf(
        "%-8s -> %d  Content-Type: %-24s %s\n",
        $uri,
        $response->status(),
        $response->header('Content-Type') ?? '-',
        $response->header('Location') ?? str_replace("\n", '\\n', substr($response->body(), 0, 40)),
    );
}

inspect($router, '/text');
inspect($router, '/json');
inspect($router, '/mutate');
inspect($router, '/payload');
inspect($router, '/old');
inspect($router, '/csv');
inspect($router, '/report');