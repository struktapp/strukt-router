<?php

require __DIR__.'/../vendor/autoload.php';

use Strukt\Contract\FormInterface;
use Strukt\Form\Form;
use Strukt\Http\Request;
use Strukt\Router\Router;

// A form declares validation rules keyed by field name. The router submits the
// request values and validates before the action runs. Rules are pipe-separated
// with `:param` arguments; see struk/form for the full set.
final class ContactForm extends Form
{
    public function rules(): array
    {
        return [
            'name' => 'required|alpha_num|max_length:40',
            'email' => 'required|email',
            'subject' => 'required|min_length:3',
            'message' => 'required|min_length:10',
        ];
    }
}

final class RegistrationForm extends Form
{
    public function rules(): array
    {
        return [
            'username' => 'required|alpha_num|min_length:3|max_length:20',
            'email' => 'required|email',
            'password' => 'required|min_length:8',
            'password_confirmation' => 'required|same:password',
            'role' => 'in:user,editor,admin',
        ];
    }
}

$router = new Router();

// The action parameter is typed FormInterface; form() names the concrete class.
$router->post('/contact', fn (FormInterface $form) => 'thanks '.$form->value('name').' <'.$form->value('email').'>')
    ->form(ContactForm::class);
$router->post('/register', fn (FormInterface $form) => 'welcome '.$form->value('username').' ('.$form->value('role').')')
    ->form(RegistrationForm::class);

function post(Router $router, string $uri, array $input): void
{
    $response = $router->kernel()->handle(new Request('POST', $uri, [], $input));
    printf("%-10s -> %d  %s\n", $uri.' '.json_encode($input), $response->status(), $response->body());
}

post($router, '/contact', ['name' => 'Ada', 'email' => 'ada@example.com', 'subject' => 'Hello', 'message' => 'A longer message body.']);
post($router, '/contact', ['name' => 'A??', 'email' => 'not-an-email', 'subject' => 'no', 'message' => 'short']);
post($router, '/register', ['username' => 'ada', 'email' => 'ada@example.com', 'password' => 'very-secret-1', 'password_confirmation' => 'very-secret-1', 'role' => 'editor']);
post($router, '/register', ['username' => 'ada', 'email' => 'ada@example.com', 'password' => 'very-secret-1', 'password_confirmation' => 'mismatch', 'role' => 'boss']);

// A failing validation answers with a 422 JSON body carrying the errors.
$response = $router->kernel()->handle(new Request('POST', '/register', [], [
    'username' => 'x', 'email' => 'bad', 'password' => 'short', 'password_confirmation' => 'nope', 'role' => 'boss',
]));
echo "\n422 payload: ".$response->body()."\n";