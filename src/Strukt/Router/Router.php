<?php

namespace Strukt\Router;

use Strukt\Container\Container;
use Strukt\Contract\AuthInterface;
use Strukt\Contract\Http\RequestInterface;
use Strukt\Contract\Http\ResponseInterface;
use Strukt\Contract\Http\SessionInterface;
use Strukt\Contract\MiddlewareInterface;
use Strukt\Router\Exception\ResolutionException;

/**
 * Fluent router registry and middleware container.
 *
 * Routes are declared with the verb methods, grouped with {@see group()}, and
 * configured with the service factories. Registered middleware is resolved by
 * name, class, object, or callable through constructor injection, so built-in
 * and custom middleware alike receive the services they declare.
 *
 * ```php
 * $router = new Router();
 *
 * $router->session(fn (): SessionInterface => new Session($_SESSION));
 * $router->verify(fn (SessionInterface $session): ?UserInterface => $session->get('user') ? ... : null);
 * $router->permissions(fn (?UserInterface $user): array => $user?->permissions ?? []);
 *
 * $router->get('/', fn () => 'home');
 * $router->post('/login', fn (RequestInterface $request) => ...)->middleware('sess');
 *
 * $response = $router->kernel()->handle($request);
 * ```
 */
final class Router
{
    /**
     * Registered routes.
     */
    private RouteCollection $routes;

    /**
     * Global middleware applied to every route.
     *
     * @var list<object|callable|string>
     */
    private array $middleware = [];

    /**
     * Named middleware definitions.
     *
     * @var array<string, object|callable|string>
     */
    private array $named = [
        'sess' => Middleware\Session::class,
        'auth' => Middleware\Authentication::class,
        'authz' => Middleware\Authorization::class,
    ];

    /**
     * Service bindings keyed by name or interface.
     *
     * @var array<string, object|\Closure>
     */
    private array $bindings = [];

    /**
     * Group prefixes awaiting pop on registration.
     *
     * @var list<string>
     */
    private array $prefixes = [];

    /**
     * Session factory, or null for an empty array-backed store.
     *
     * @var \Closure|null
     */
    private ?\Closure $sessionFactory = null;

    /**
     * User resolver, or null to hydrate from the auth gate.
     *
     * @var \Closure|null
     */
    private ?\Closure $verifyFactory = null;

    /**
     * Granted-permission resolver.
     *
     * @var \Closure|null
     */
    private ?\Closure $permissionsFactory = null;

    /**
     * Request factory used by the kernel when none is supplied.
     *
     * @var \Closure|null
     */
    private ?\Closure $globalsFactory = null;

    /**
     * Optional dependency container backing service resolution.
     *
     * When present, named bindings are mirrored into it, and middleware,
     * route actions, and the session/auth gates fall back to it for anything
     * not bound on the router directly.
     */
    private ?Container $container;

    public function __construct(?Container $container = null)
    {
        $this->routes = new RouteCollection();
        $this->container = $container;
    }

    /**
     * Returns the dependency container backing resolution, when one is set.
     *
     * @return Container|null Dependency container, or null.
     */
    public function container(): ?Container
    {
        return $this->container;
    }

    /**
     * Declares a GET route.
     *
     * @param string $pattern Route pattern such as `/user/{id:int}`.
     * @param callable $action Action callable.
     * @return Route The registered route for option chaining.
     */
    public function get(string $pattern, callable $action): Route
    {
        return $this->register('GET', $pattern, $action);
    }

    /**
     * Declares a POST route.
     *
     * @param string $pattern Route pattern.
     * @param callable $action Action callable.
     * @return Route The registered route for option chaining.
     */
    public function post(string $pattern, callable $action): Route
    {
        return $this->register('POST', $pattern, $action);
    }

    /**
     * Declares a PUT route.
     *
     * @param string $pattern Route pattern.
     * @param callable $action Action callable.
     * @return Route The registered route for option chaining.
     */
    public function put(string $pattern, callable $action): Route
    {
        return $this->register('PUT', $pattern, $action);
    }

    /**
     * Declares a PATCH route.
     *
     * @param string $pattern Route pattern.
     * @param callable $action Action callable.
     * @return Route The registered route for option chaining.
     */
    public function patch(string $pattern, callable $action): Route
    {
        return $this->register('PATCH', $pattern, $action);
    }

    /**
     * Declares a DELETE route.
     *
     * @param string $pattern Route pattern.
     * @param callable $action Action callable.
     * @return Route The registered route for option chaining.
     */
    public function delete(string $pattern, callable $action): Route
    {
        return $this->register('DELETE', $pattern, $action);
    }

    /**
     * Declares an OPTIONS route.
     *
     * @param string $pattern Route pattern.
     * @param callable $action Action callable.
     * @return Route The registered route for option chaining.
     */
    public function options(string $pattern, callable $action): Route
    {
        return $this->register('OPTIONS', $pattern, $action);
    }

    /**
     * Declares a HEAD route.
     *
     * @param string $pattern Route pattern.
     * @param callable $action Action callable.
     * @return Route The registered route for option chaining.
     */
    public function head(string $pattern, callable $action): Route
    {
        return $this->register('HEAD', $pattern, $action);
    }

    /**
     * Declares a route for every common HTTP method.
     *
     * @param string $pattern Route pattern.
     * @param callable $action Action callable.
     * @return Route The registered route for option chaining.
     */
    public function any(string $pattern, callable $action): Route
    {
        return $this->register(['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS', 'HEAD'], $pattern, $action);
    }

    /**
     * Opens a grouped scope whose routes share a path prefix.
     *
     * @param string $prefix Prefix such as `/api/v1`.
     * @param callable $group Callback receiving this router.
     * @return void
     */
    public function group(string $prefix, callable $group): void
    {
        $this->prefixes[] = $prefix;

        try {
            $group($this);
        } finally {
            array_pop($this->prefixes);
        }
    }

    /**
     * Appends global middleware applied to every route.
     *
     * @param object|callable|string|list<object|callable|string> ...$definitions Middleware definitions.
     * @return static This router.
     */
    public function middleware(object|callable|string|array ...$definitions): static
    {
        foreach ($definitions as $definition) {
            if (is_array($definition)) {
                $this->middleware = [...$this->middleware, ...$definition];
            } else {
                $this->middleware[] = $definition;
            }
        }

        return $this;
    }

    /**
     * Registers a named middleware definition.
     *
     * @param string $name Middleware name.
     * @param object|callable|string $definition Class, object, or callable.
     * @return static This router.
     */
    public function named(string $name, object|callable|string $definition): static
    {
        $this->named[$name] = $definition;

        return $this;
    }

    /**
     * Binds a service named by an identifier or an interface.
     *
     * The binding is mirrored into the dependency container when one is set,
     * so middleware and actions can also resolve it by type.
     *
     * @param string $id Identifier, such as an interface name.
     * @param object|\Closure $resolver Instance, or factory returning one.
     * @return static This router.
     */
    public function bind(string $id, object $resolver): static
    {
        $this->bindings[$id] = $resolver;

        if ($this->container !== null) {
            if ($resolver instanceof \Closure) {
                $this->container->factory($id, $resolver);
            } else {
                $this->container->instance($id, $resolver);
            }
        }

        return $this;
    }

    /**
     * Configures the session factory.
     *
     * @param callable $factory Factory `fn(): SessionInterface`.
     * @return static This router.
     */
    public function session(callable $factory): static
    {
        $this->sessionFactory = $factory instanceof \Closure ? $factory : \Closure::fromCallable($factory);

        return $this;
    }

    /**
     * Configures the user resolver.
     *
     * @param callable $factory Resolver `fn(SessionInterface): ?UserInterface`.
     * @return static This router.
     */
    public function verify(callable $factory): static
    {
        $this->verifyFactory = $factory instanceof \Closure ? $factory : \Closure::fromCallable($factory);

        return $this;
    }

    /**
     * Configures the granted-permission resolver.
     *
     * @param callable $factory Resolver `fn(?UserInterface): list<string>`.
     * @return static This router.
     */
    public function permissions(callable $factory): static
    {
        $this->permissionsFactory = $factory instanceof \Closure ? $factory : \Closure::fromCallable($factory);

        return $this;
    }

    /**
     * Configures the request factory used when the kernel receives none.
     *
     * @param callable $factory Factory `fn(): RequestInterface`.
     * @return static This router.
     */
    public function globals(callable $factory): static
    {
        $this->globalsFactory = $factory instanceof \Closure ? $factory : \Closure::fromCallable($factory);

        return $this;
    }

    /**
     * Resolves the current session store.
     *
     * The dependency container's `SessionInterface` (or `session`) binding wins
     * when present, then the configured factory, then an empty store.
     *
     * @return SessionInterface Session from the container, factory, or an empty store.
     */
    public function resolveSession(): SessionInterface
    {
        $session = null;

        if ($this->container !== null) {
            $id = $this->container->bound(SessionInterface::class)
                ? SessionInterface::class
                : 'session';
            if ($this->container->bound($id)) {
                $session = $this->container->get($id);
            }
        }

        if ($session === null) {
            $session = $this->sessionFactory !== null ? ($this->sessionFactory)() : new \Strukt\Session();
        }

        if (!$session instanceof SessionInterface) {
            throw new ResolutionException('The session factory must return a SessionInterface.');
        }

        return $session;
    }

    /**
     * Resolves the auth gate for a session.
     *
     * @param SessionInterface $session Store backing the gate.
     * @return AuthInterface Auth gate from the container, a binding, or a session-backed default.
     */
    public function resolveAuth(SessionInterface $session): AuthInterface
    {
        $auth = null;

        if ($this->container !== null) {
            $id = $this->container->bound(AuthInterface::class) ? AuthInterface::class : 'auth';
            if ($this->container->bound($id)) {
                $auth = $this->container->get($id);
            }
        }

        if ($auth === null) {
            $binding = $this->bindings[AuthInterface::class] ?? $this->bindings['auth'] ?? null;
            $auth = $binding instanceof \Closure ? $binding() : $binding;
        }

        if ($auth === null) {
            $auth = new \Strukt\Auth($session);
        }

        if (!$auth instanceof AuthInterface) {
            throw new ResolutionException('The auth binding must satisfy the AuthInterface.');
        }

        return $auth;
    }

    /**
     * Reports whether an identifier carries a binding.
     *
     * Consults the router bindings first, then the dependency container's
     * explicit bindings (not autowirable classes).
     *
     * @param string $id Binding identifier.
     * @return bool True when bound.
     */
    public function has(string $id): bool
    {
        return array_key_exists($id, $this->bindings)
            || ($this->container !== null && $this->container->bound($id));
    }

    /**
     * Resolves a named binding.
     *
     * @param string $id Binding identifier.
     * @return mixed Bound value, result of the factory, or null when unbound.
     */
    public function resolve(string $id): mixed
    {
        if ($this->container !== null && $this->container->bound($id)) {
            return $this->container->get($id);
        }

        $binding = $this->bindings[$id] ?? null;

        return $binding instanceof \Closure ? $binding() : $binding;
    }

    /**
     * Builds the per-request service map passed through the kernel.
     *
     * When a dependency container is set, the resolved session and auth gate
     * are published into it so middleware and actions can type-hint them.
     *
     * @return array{session: SessionInterface, auth: AuthInterface, verify: ?callable, permissions: ?callable, bindings: array<string, object|\Closure>} Services.
     */
    public function services(): array
    {
        $session = $this->resolveSession();
        $auth = $this->resolveAuth($session);

        if ($this->container !== null) {
            $this->container
                ->instance(SessionInterface::class, $session)
                ->instance(AuthInterface::class, $auth)
                ->instance('session', $session)
                ->instance('auth', $auth);
        }

        return [
            'session' => $session,
            'auth' => $auth,
            'verify' => $this->verifyFactory,
            'permissions' => $this->permissionsFactory ?? static fn (?\Strukt\Contract\UserInterface $user = null): array => [],
            'bindings' => $this->bindings,
        ];
    }

    /**
     * Resolves a middleware definition into a middleware instance.
     *
     * @param object|callable|string $middleware Name, class, object, or callable.
     * @param array<string, mixed> $services Per-request service map.
     * @return MiddlewareInterface Resolved middleware.
     *
     * @throws ResolutionException When the definition cannot be resolved.
     */
    public function makeMiddleware(object|callable|string $middleware, array $services): MiddlewareInterface
    {
        if (is_string($middleware) && array_key_exists($middleware, $this->named)) {
            $middleware = $this->named[$middleware];
        }

        if ($middleware instanceof MiddlewareInterface) {
            return $middleware;
        }

        if (is_string($middleware)) {
            if (!class_exists($middleware)) {
                throw new ResolutionException("Middleware class [{$middleware}] was not found.");
            }

            $instance = $this->instantiate($middleware, $services);
            if (!$instance instanceof MiddlewareInterface) {
                throw new ResolutionException("Middleware [{$middleware}] must implement the MiddlewareInterface.");
            }

            return $instance;
        }

        if (is_callable($middleware)) {
            $middleware = \Closure::fromCallable($middleware);

            return new class($middleware) implements MiddlewareInterface {
                /**
                 * @param \Closure $middleware Callable middleware.
                 */
                public function __construct(private \Closure $middleware)
                {
                }

                public function process(RequestInterface $request, ResponseInterface $response, callable $next): ResponseInterface
                {
                    return ($this->middleware)($request, $response, $next);
                }
            };
        }

        throw new ResolutionException('Middleware must be a name, a class, an object, or a callable.');
    }

    /**
     * Resolves the exact route for a path and method.
     *
     * @param string $path Request path.
     * @param string $method Request method.
     * @return array{route: Route, params: array<string, mixed>} Matched route and typed params.
     */
    public function match(string $path, string $method): array
    {
        return $this->routes->match($path, $method);
    }

    /**
     * Reconstructs a URI from a named route.
     *
     * @param string $name Route name.
     * @param array<string, mixed> $params Parameter values.
     * @return string URI such as `/user/7`.
     *
     * @throws ResolutionException When no route carries the name.
     */
    public function path(string $name, array $params = []): string
    {
        $route = $this->routes->byName($name);
        if ($route === null) {
            throw new ResolutionException("Route [{$name}] was not found.");
        }

        return $route->uri($params);
    }

    /**
     * @return list<object|callable|string> Global middleware definitions.
     */
    public function middlewareList(): array
    {
        return $this->middleware;
    }

    /**
     * @return array<string, object|callable|string> Named middleware definitions.
     */
    public function namedMiddleware(): array
    {
        return $this->named;
    }

    /**
     * @return array<string, object|\Closure> Service bindings.
     */
    public function bindings(): array
    {
        return $this->bindings;
    }

    /**
     * @return \Closure|null Request factory, or null when unset.
     */
    public function globalsFactory(): ?\Closure
    {
        return $this->globalsFactory ?: null;
    }

    public function collection(): RouteCollection
    {
        return $this->routes;
    }

    /**
     * Builds the dispatch kernel.
     *
     * @return Kernel Kernel bound to this router.
     */
    public function kernel(): Kernel
    {
        return new Kernel($this);
    }

    /**
     * Registers a route with the current group prefix.
     *
     * @param string|list<string> $methods Accepted methods.
     * @param string $pattern Route pattern.
     * @param callable $action Action callable.
     * @return Route The registered route.
     */
    private function register(string|array $methods, string $pattern, callable $action): Route
    {
        $action = $action instanceof \Closure ? $action : \Closure::fromCallable($action);

        return $this->routes->add(new Route($methods, $this->applyPrefix($pattern), $action));
    }

    /**
     * Applies the open group prefixes to a pattern.
     *
     * @param string $pattern Route pattern.
     * @return string Prefixed pattern.
     */
    private function applyPrefix(string $pattern): string
    {
        if ($this->prefixes === []) {
            return $pattern;
        }

        $prefix = implode('/', array_map('trim', $this->prefixes));

        return '/' . trim($prefix, '/') . '/' . trim($pattern, '/');
    }

    /**
     * Constructs a class, resolving its constructor arguments from the services.
     *
     * @param class-string $class Class to construct.
     * @param array<string, mixed> $services Per-request service map.
     * @return object Constructed instance.
     *
     * @throws ResolutionException When an argument cannot be resolved.
     */
    private function instantiate(string $class, array $services): object
    {
        $reflection = new \ReflectionClass($class);
        $constructor = $reflection->getConstructor();

        if ($constructor === null) {
            return $reflection->newInstance();
        }

        $args = [];
        foreach ($constructor->getParameters() as $parameter) {
            $name = $parameter->getName();
            $type = $parameter->getType();
            $typeName = $type instanceof \ReflectionNamedType ? $type->getName() : null;

            if (array_key_exists($name, $this->bindings)) {
                $args[] = $this->resolve($name);

                continue;
            }

            if ($name === 'verify' && array_key_exists('verify', $services)) {
                $args[] = $services['verify'];

                continue;
            }

            if ($name === 'permissions' && array_key_exists('permissions', $services)) {
                $args[] = $services['permissions'];

                continue;
            }

            if ($typeName === SessionInterface::class) {
                $args[] = $services['session'];

                continue;
            }

            if ($typeName === AuthInterface::class) {
                $args[] = $services['auth'];

                continue;
            }

            if ($typeName !== null && array_key_exists($typeName, $this->bindings)) {
                $args[] = $this->resolve($typeName);

                continue;
            }

            if ($typeName !== null && $this->container !== null && $this->container->bound($typeName)) {
                $args[] = $this->container->get($typeName);

                continue;
            }

            if ($parameter->isDefaultValueAvailable()) {
                $args[] = $parameter->getDefaultValue();

                continue;
            }

            if ($type !== null && $type->allowsNull()) {
                $args[] = null;

                continue;
            }

            throw new ResolutionException(sprintf('Unable to resolve middleware parameter [%s] of [%s].', $name, $class));
        }

        return $reflection->newInstanceArgs($args);
    }
}