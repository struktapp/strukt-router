<?php

namespace Strukt\Router\Fixtures;

use Strukt\Form\Form;

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