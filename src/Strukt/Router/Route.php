<?php

namespace Strukt\Router;

use Closure;

/**
 * A registered route: methods, pattern, action, and route options.
 *
 * Routes are built through the {@see Router} DSL, which returns this object so
 * callers can stack route options before registration is final:
 *
 * ```php
 * $route = $router->get('/user/{id:int}', fn (int $id) => ...);
 * ```
 *
 * Matching is delegated to the {@see UrlMatcher}, so placeholders obey the
 * same typing rules. Options are assigned fluently after registration and
 * mutate the registered route in place.
 */
final class Route
{
    /**
     * Optional route name used for reverse generation.
     */
    private ?string $name = null;

    /**
     * Middleware to run before the action.
     *
     * @var list<object|callable|string>
     */
    private array $middleware = [];

    /**
     * Permission names required to reach the action.
     *
     * @var list<string>
     */
    private array $permissions = [];

    /**
     * Whether the action requires an authenticated user.
     */
    private bool $requiresAuth = false;

    /**
     * Form class validated before the action runs.
     *
     * @var class-string<Strukt\Contract\FormInterface>|null
     */
    private ?string $form = null;

    /**
     * Default parameter values used for reverse generation.
     *
     * @var array<string, mixed>
     */
    private array $defaults = [];

    /**
     * @param string|list<string> $methods Methods the route accepts.
     * @param string $pattern Route pattern, such as `/user/{id:int}`.
     * @param callable $action Action invoked with the resolved arguments.
     */
    public function __construct(
        private readonly string|array $methods,
        private readonly string $pattern,
        private readonly Closure $action,
    ) {
    }

    /**
     * Lists the accepted methods, normalized and uppercased.
     *
     * @return list<string> Methods such as `GET` or `POST`.
     */
    public function methods(): array
    {
        $methods = is_array($this->methods) ? $this->methods : [$this->methods];

        return array_values(array_unique(array_map('strtoupper', $methods)));
    }

    public function pattern(): string
    {
        return $this->pattern;
    }

    public function action(): callable
    {
        return $this->action;
    }

    /**
     * Assigns the route name used for reverse generation.
     *
     * @param string $name Route name.
     * @return static This route.
     */
    public function name(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Reads the route name.
     *
     * @return string|null Route name, or null when unnamed.
     */
    public function getName(): ?string
    {
        return $this->name;
    }

    /**
     * Appends middleware to the route.
     *
     * @param object|callable|string|list<object|callable|string> $middleware Middleware to append.
     * @return static This route.
     */
    public function middleware(object|callable|string|array $middleware): static
    {
        $items = is_array($middleware) ? $middleware : [$middleware];
        $this->middleware = [...$this->middleware, ...$items];

        return $this;
    }

    /**
     * @return list<object|callable|string> Route middleware definitions.
     */
    public function getMiddleware(): array
    {
        return $this->middleware;
    }

    /**
     * Declares the permission names required to reach the action.
     *
     * @param list<string> $permissions Permission names.
     * @return static This route.
     */
    public function permissions(array $permissions): static
    {
        $this->permissions = $permissions;

        return $this;
    }

    /**
     * @return list<string> Permission names required by the route.
     */
    public function getPermissions(): array
    {
        return $this->permissions;
    }

    /**
     * Marks the route as requiring an authenticated user.
     *
     * @return static This route.
     */
    public function requiresAuth(): static
    {
        $this->requiresAuth = true;

        return $this;
    }

    /**
     * Checks whether the route requires an authenticated user.
     *
     * @return bool True when the route declares the requirement.
     */
    public function authRequired(): bool
    {
        return $this->requiresAuth;
    }

    /**
     * Associates a form class that is submitted and validated before the action.
     *
     * @param class-string<Strukt\Contract\FormInterface> $form Form class.
     * @return static This route.
     */
    public function form(string $form): static
    {
        $this->form = $form;

        return $this;
    }

    /**
     * @return class-string<Strukt\Contract\FormInterface>|null Form class, or null when unset.
     */
    public function getForm(): ?string
    {
        return $this->form;
    }

    /**
     * Assigns default parameter values used for reverse generation.
     *
     * @param array<string, mixed> $defaults Values keyed by placeholder name.
     * @return static This route.
     */
    public function defaults(array $defaults): static
    {
        $this->defaults = $defaults;

        return $this;
    }

    /**
     * @return array<string, mixed> Default parameter values.
     */
    public function getDefaults(): array
    {
        return $this->defaults;
    }

    /**
     * Checks whether the route accepts a method.
     *
     * @param string $method Method to compare, case-insensitively.
     * @return bool True when the route accepts the method.
     */
    public function allows(string $method): bool
    {
        return in_array(strtoupper($method), $this->methods(), true);
    }

    /**
     * Extracts typed parameters from a path.
     *
     * @param string $path Request path.
     * @return array<string, mixed>|null Param values, or null when the path does not match.
     */
    public function params(string $path): ?array
    {
        return (new UrlMatcher())->params($this->pattern, $path);
    }

    /**
     * Reconstructs the URI from a parameter map and any route defaults.
     *
     * @param array<string, mixed> $params Parameter values keyed by placeholder name.
     * @return string URI such as `/user/7`.
     *
     * @throws \InvalidArgumentException When a required parameter is missing.
     */
    public function uri(array $params = []): string
    {
        return (new UrlMatcher())->uri($this->pattern, array_merge($this->defaults, $params));
    }
}