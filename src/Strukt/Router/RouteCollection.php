<?php

namespace Strukt\Router;

use Strukt\Router\Exception\MethodNotAllowedException;
use Strukt\Router\Exception\RouteNotFoundException;

/**
 * Ordered store of registered routes with path and method resolution.
 *
 * Routes are kept in registration order and matched by insertion, so the first
 * declared route wins for a given path. A path that matches some route but not
 * the request method reports the methods that would have been accepted.
 */
final class RouteCollection
{
    /**
     * Registered routes in declaration order.
     *
     * @var list<Route>
     */
    private array $routes = [];

    /**
     * @var array<string, Route> Routes keyed by name.
     */
    private array $named = [];

    /**
     * Registers a route and indexes it by name when one is present.
     *
     * @param Route $route Route to add.
     * @return Route The added route.
     */
    public function add(Route $route): Route
    {
        $this->routes[] = $route;

        if ($route->getName() !== null) {
            $this->named[$route->getName()] = $route;
        }

        return $route;
    }

    /**
     * Lists every registered route.
     *
     * @return list<Route> Routes in declaration order.
     */
    public function all(): array
    {
        return $this->routes;
    }

    /**
     * Counts the registered routes.
     *
     * @return int Route count.
     */
    public function count(): int
    {
        return count($this->routes);
    }

    /**
     * Looks up a route by its name.
     *
     * The name map is cached at registration time and falls back to a scan for
     * names assigned to an already-registered route.
     *
     * @param string $name Route name.
     * @return Route|null The route, or null when unknown.
     */
    public function byName(string $name): ?Route
    {
        if (isset($this->named[$name])) {
            return $this->named[$name];
        }

        foreach ($this->routes as $route) {
            if ($route->getName() === $name) {
                return $route;
            }
        }

        return null;
    }

    /**
     * Resolves a request path and method to a route and its parameters.
     *
     * @param string $path Request path.
     * @param string $method Request method.
     * @return array{route: Route, params: array<string, mixed>} Matched route and typed params.
     *
     * @throws RouteNotFoundException When no route matches the path.
     * @throws MethodNotAllowedException When the path matches but the method does not.
     */
    public function match(string $path, string $method): array
    {
        $matches = [];
        foreach ($this->routes as $route) {
            $params = $route->params($path);
            if ($params !== null) {
                $matches[] = [$route, $params];
            }
        }

        if ($matches === []) {
            throw new RouteNotFoundException();
        }

        foreach ($matches as [$route, $params]) {
            if ($route->allows($method)) {
                return ['route' => $route, 'params' => $params];
            }
        }

        throw new MethodNotAllowedException($this->allowedMethods($path));
    }

    /**
     * Lists the methods that would match the path.
     *
     * @param string $path Request path.
     * @return list<string> Acceptable methods merged and uppercased.
     */
    public function allowedMethods(string $path): array
    {
        $methods = [];
        foreach ($this->routes as $route) {
            if ($route->params($path) !== null) {
                $methods = [...$methods, ...$route->methods()];
            }
        }

        return array_values(array_unique($methods));
    }
}