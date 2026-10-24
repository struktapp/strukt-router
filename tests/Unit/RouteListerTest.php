<?php

use Strukt\Router\Cli\RouteLister;
use Strukt\Router\Router;

it('renders one row per route in registration order', function () {
    $router = new Router();
    $router->get('/', fn () => 'home')->name('home');
    $router->post('/ping', fn () => 'pong');

    $rows = (new RouteLister($router))->rows();

    expect($rows)->toHaveCount(2)
        ->and($rows[0])->toBe([
            'methods' => 'GET',
            'pattern' => '/',
            'name' => 'home',
            'auth' => '-',
        ])
        ->and($rows[1])->toBe([
            'methods' => 'POST',
            'pattern' => '/ping',
            'name' => '-',
            'auth' => '-',
        ]);
});

it('describes the gates a route carries', function () {
    $router = new Router();
    $router->get('/open', fn () => 'open');
    $router->get('/members', fn () => 'members')->requiresAuth();
    $router->get('/reports', fn () => 'reports')->permissions(['reports.read']);
    $router->get('/admin', fn () => 'admin')->requiresAuth()->permissions(['users.manage']);

    $auth = array_column((new RouteLister($router))->rows(), 'auth');

    expect($auth)->toBe([
        '-',
        'auth',
        'perm: reports.read',
        'auth, perm: users.manage',
    ]);
});

it('renders an aligned table with a header and one line per route', function () {
    $router = new Router();
    $router->get('/', fn () => 'home')->name('home');
    $router->get('/user/{id:int}', fn (int $id) => "user {$id}")->name('user.show')->requiresAuth();

    $table = (new RouteLister($router))->render();

    expect($table)->toBe(<<<'TABLE'
METHODS  PATTERN         NAME       AUTH
-------  --------------  ---------  ----
GET      /               home       -
GET      /user/{id:int}  user.show  auth

TABLE);
});

it('reports an empty collection instead of an empty table', function () {
    expect((new RouteLister(new Router()))->render())->toBe("No routes registered.\n");
});

it('lists the routes declared by a bootstrap file', function () {
    $file = dirname(__DIR__).'/Fixtures/routes.php';

    $router = RouteLister::load($file);

    expect($router)->toBeInstanceOf(Router::class)
        ->and($router->collection()->count())->toBe(4);
});

it('builds a fresh router on every load', function () {
    $file = dirname(__DIR__).'/Fixtures/routes.php';

    expect(RouteLister::load($file)->collection()->count())->toBe(4)
        ->and(RouteLister::load($file)->collection()->count())->toBe(4);
});

it('rejects a missing routes file', function () {
    RouteLister::load('/nonexistent/routes.php');
})->throws(InvalidArgumentException::class, 'Routes file [/nonexistent/routes.php] was not found.');

it('rejects a routes file that does not return a router', function () {
    RouteLister::load(dirname(__DIR__).'/Fixtures/not-a-router.php');
})->throws(InvalidArgumentException::class, 'must return a Strukt\Router\Router instance.');

it('lists routes from the binary against a routes file', function () {
    [$status, $stdout] = run_strukt_routes([dirname(__DIR__).'/Fixtures/routes.php']);

    expect($status)->toBe(0)
        ->and($stdout)->toContain('METHODS')
        ->and($stdout)->toContain('/user/{id:int}')
        ->and($stdout)->toContain('user.show')
        ->and($stdout)->toContain('auth')
        ->and($stdout)->toContain('perm: reports.create');
});

it('exits with an error when the routes file is missing', function () {
    [$status, $stdout, $stderr] = run_strukt_routes(['/nonexistent/routes.php']);

    expect($status)->toBe(1)
        ->and($stdout)->toBe('')
        ->and($stderr)->toContain('Routes file [/nonexistent/routes.php] was not found.');
});

it('prints usage for --help', function () {
    [$status, $stdout] = run_strukt_routes(['--help']);

    expect($status)->toBe(0)
        ->and($stdout)->toContain('Usage: strukt-routes');
});

it('rejects unknown options', function () {
    [$status, , $stderr] = run_strukt_routes(['--loud']);

    expect($status)->toBe(1)
        ->and($stderr)->toContain('Unknown option [--loud].');
});
