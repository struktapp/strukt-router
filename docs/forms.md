# Forms and validation

A route can declare a form class. The router submits the request values to it,
validates, and injects the validated form into the action — or answers 422
with the failures.

- [Declaring a form](#declaring-a-form)
- [Attaching it to a route](#attaching-it-to-a-route)
- [What the action receives](#what-the-action-receives)
- [The rule table](#the-rule-table)
- [Validation behaviour](#validation-behaviour)
- [Custom messages](#custom-messages)
- [Custom rules](#custom-rules)
- [The 422 payload](#the-422-payload)
- [Validating outside a route](#validating-outside-a-route)

## Declaring a form

Extend `Strukt\Form\Form` (from `strukt/form`) and declare `rules()` keyed by
field name. A definition is a pipe-separated string or a list of rule tokens:

```php
use Strukt\Form\Form;

final class LoginForm extends Form
{
    public function rules(): array
    {
        return [
            'email' => 'required|email',
            'password' => 'required|min_length:8',
        ];
    }
}
```

Rules may take parameters after a colon (`min_length:8`), and rules that
accept lists split on commas (`in:user,editor,admin`).

## Attaching it to a route

```php
$router->post('/login', fn (FormInterface $form) => 'hi '.$form->value('email'))
    ->form(LoginForm::class);
```

`Route::form()` stores the class name; nothing is constructed until a request
is dispatched.

## What the action receives

The router only injects a form when an action parameter is typed with the
**`FormInterface`**:

```php
// correct — resolved by the Invoker
fn (FormInterface $form) => $form->value('email')

// not a form parameter — the Invoker has no entry for LoginForm, so this
// fails to resolve and the kernel answers 500
fn (LoginForm $form) => …
```

The concrete class always comes from `Route::form()`. When the action runs,
the sequence is:

```
new LoginForm()
form->submit($request->all())      query + body merged, body wins on collision
form->validate()
    ├── passes → form injected, action runs
    └── fails  → ValidationException → 422 JSON, action never runs
```

`$request->all()` merges query-string and body values, so both
`POST /login?ref=nav` and a form body feed the same submission.

## The rule table

| Rule | Parameters | Passes when |
| --- | --- | --- |
| `required` | — | the value is not empty (`null`, `''`, `[]` all count as empty) |
| `nullable` | — | the value is not `null` |
| `filled` | — | the value is not empty |
| `string` | — | `is_string()` |
| `integer` | — | the value is an integer-like value |
| `numeric` | — | `is_numeric()` |
| `boolean` | — | the value is boolean-like |
| `array` | — | `is_array()` |
| `email` | — | a valid email address |
| `url` | — | a valid URL |
| `uuid` | — | a valid UUID |
| `ip` | — | a valid IP address |
| `alpha` | — | letters only |
| `alpha_num` | — | letters and numbers only |
| `min` | `:0` | numeric value ≥ `:0` |
| `max` | `:0` | numeric value ≤ `:0` |
| `between` | `:0,:1` | numeric value between `:0` and `:1` |
| `length` | `:0` | string of exactly `:0` characters |
| `min_length` | `:0` | string of at least `:0` characters |
| `max_length` | `:0` | string of at most `:0` characters |
| `min_count` | `:0` | array with at least `:0` items |
| `max_count` | `:0` | array with at most `:0` items |
| `in` | `:list` | the value is one of the listed options (loose comparison) |
| `not_in` | `:list` | the value is none of the listed options |
| `same` | `:field` | strictly equals another field |
| `different` | `:field` | is strictly different from another field |
| `regex` | `:pattern` | matches the pattern |
| `date` | `:format?` | a valid date in the given format |

Message placeholders: `:0`, `:1` are the rule's parameters, `:list` is the
joined option list.

Note that `min`/`max`/`between` are **numeric**: they do not measure string
length. Use `min_length`/`max_length` for text.

## Validation behaviour

Three field-level rules keep the reported errors useful rather than noisy:

1. **Optional fields skip when blank.** A field without `required` whose value
   is `null`, `''`, or `[]` skips its remaining rules — a blank optional field
   does not also report a type error.
2. **`nullable` accepts `null` outright.**
3. **Bailing rules end the field.** `required`, `filled`, `string`,
   `integer`, `numeric`, `boolean`, and `array` stop the field after a
   failure, so later rules do not pile messages onto the same value.

Failures are collected **per field**, in declaration order, and fields that
pass are omitted from the error map entirely.

## Custom messages

Override any message keyed by `field.rule`, or by the bare rule name to change
it everywhere the form uses it:

```php
final class SignupForm extends Form
{
    public function rules(): array
    {
        return [
            'email' => 'required|email',
            'password' => 'required|min_length:12',
        ];
    }

    public function messages(): array
    {
        return [
            'email.required' => 'We need an email address to send the receipt.',
            'min_length' => 'Give us at least :0 characters, please.',
        ];
    }
}
```

## Custom rules

The shared registry accepts new rules with `extend()`. A name that collides
with a built-in is rejected. A rule implements three methods — `passes()`,
`message()`, and `bails()`:

```php
use Strukt\Form\Contract\FieldSource;
use Strukt\Form\Contract\RuleInterface;
use Strukt\Form\RuleRegistry;

final class EvenRule implements RuleInterface
{
    public function __construct(private readonly string $field) {}

    public function passes(mixed $value, FieldSource $fields): bool
    {
        return is_numeric($value) && ((int) $value) % 2 === 0;
    }

    public function message(): string
    {
        return ucfirst($this->field).' must be an even number.';
    }

    public function bails(): bool
    {
        return false;
    }
}

RuleRegistry::shared()->extend('even', fn (string $field, array $params) => new EvenRule($field));
```

Then use it like any built-in: `'qty' => 'required|even'`.

To give **one form** its own registry instead of touching the shared one,
override `registry()`:

```php
protected function registry(): RuleRegistry
{
    return $this->own ??= RuleRegistry::create()->extend('even', …);
}
```

## The 422 payload

When validation fails the kernel never reaches the action. It catches
`ValidationException` and answers:

```http
HTTP/1.1 422 Unprocessable Entity
Content-Type: application/json

{"errors":{"email":["Email must be a valid email address."],
           "password":["Password must be at least 8 characters."]}}
```

Reading it in a client:

```php
$response = $router->kernel()->handle(new Request('POST', '/login', [], $input));
if ($response->status() === 422) {
    $errors = json_decode($response->body(), true)['errors'];
}
```

## Validating outside a route

Forms are usable standalone — useful for console commands and tests:

```php
$form = new LoginForm();

$form->submit(['email' => 'nope', 'password' => 'short']);
$form->validate();          // false
$form->errors();            // ['email' => [...], 'password' => [...]]
$form->errorsFor('email');  // just that field's messages

$form->reset()->submit(['email' => 'ada@example.com', 'password' => 'long-enough']);
$form->validate();          // true
```

`validateWith($values)` is the shorthand for `submit()+validate()`, and
`fields()` lists the declared field names.

## Next steps

- [Responses and errors](responses.md) for the 422 mapping and other statuses.
- [`examples/05-form-validation.php`](../examples/05-form-validation.php) for
  a runnable demonstration.
- [Troubleshooting](troubleshooting.md) when a form is not injected.
