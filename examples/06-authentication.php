<?php

require __DIR__.'/../vendor/autoload.php';

use Strukt\Auth;
use Strukt\Contract\AuthInterface;
use Strukt\Contract\FormInterface;
use Strukt\Contract\Http\RequestInterface;
use Strukt\Contract\Http\SessionInterface;
use Strukt\Contract\UserInterface;
use Strukt\Form\Form;
use Strukt\Http\Request;
use Strukt\Router\Router;
use Strukt\Session;
use Strukt\User;

final class LoginForm extends Form
{
    public function rules(): array
    {
        return [
            'email' => 'required|email',
            'password' => 'required|min_length:6',
        ];
    }
}

// A single store is captured so a login in one dispatch persists for the next.
// In a real app the factory would hand back the store for the current session.
$store = new Session();
$router = new Router();

$router->session(fn (): SessionInterface => $store);
// verify() decides who is logged in for requiresAuth()/permissions() routes.
// Auth::login() persists the user under the Auth::SESSION_* keys, so read the
// email from Auth::SESSION_EMAIL rather than a hand-rolled key.
$router->verify(fn (SessionInterface $session): ?UserInterface => $session->get(Auth::SESSION_EMAIL) !== null
    ? new User(email: $session->get(Auth::SESSION_EMAIL))
    : null);
$router->permissions(fn (?UserInterface $user): array => $user?->getEmail() === 'admin@example.com' ? ['admin'] : []);

// Guest-facing routes.
$router->get('/status', fn (AuthInterface $auth) => $auth->guest() ? 'guest' : 'authenticated');
$router->get('/home', fn () => 'welcome guest')->name('home');

// Logging in writes the user into the shared store through the auth gate.
// Auth::login() requires an identifier or a username on the user record.
$router->post('/login', fn (AuthInterface $auth, FormInterface $form): array => [
    'logged_in' => $auth->login(new User(email: $form->value('email'), username: $form->value('email'))),
    'email' => $form->value('email'),
])->form(LoginForm::class);

// Protected routes opt in with requiresAuth() and optionally permissions().
$router->get('/dashboard', fn (RequestInterface $request) => 'hi '.($request->user()?->getEmail() ?? 'guest'))
    ->requiresAuth();
$router->get('/admin', fn (RequestInterface $request) => 'panel of '.$request->user()?->getEmail())
    ->requiresAuth()
    ->permissions(['admin']);

function dispatch(Router $router, string $method, string $uri, array $input = []): void
{
    $response = $router->kernel()->handle(new Request($method, $uri, [], $input));
    printf("%-6s %-26s -> %d  %s\n", $method, $uri, $response->status(), $response->body());
}

echo "As a guest:\n";
dispatch($router, 'GET', '/status');
dispatch($router, 'GET', '/dashboard');          // 401 Authentication required!
dispatch($router, 'GET', '/admin');              // 401 before any permission check

echo "\nLogin as an editor:\n";
dispatch($router, 'POST', '/login', ['email' => 'ada@example.com', 'password' => 'secret1']);
dispatch($router, 'GET', '/status');
dispatch($router, 'GET', '/dashboard');          // 200 hi ada@example.com
dispatch($router, 'GET', '/admin');              // 403 lacks the admin permission

echo "\nLogin as an admin:\n";
dispatch($router, 'POST', '/login', ['email' => 'admin@example.com', 'password' => 'secret1']);
dispatch($router, 'GET', '/admin');              // 200 panel of admin@example.com