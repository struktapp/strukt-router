<?php

namespace Strukt\Router;

use Strukt\Contract\Http\DownloadInterface;
use Strukt\Contract\Http\RequestInterface;
use Strukt\Contract\Http\ResponseInterface;
use Strukt\Contract\MiddlewareInterface;
use Strukt\Contract\UserInterface;
use Strukt\Http\Request;
use Strukt\Http\Response;
use Strukt\Router\Exception\AuthorizationException;
use Strukt\Router\Exception\MethodNotAllowedException;
use Strukt\Router\Exception\ResolutionException;
use Strukt\Router\Exception\RouteNotFoundException;
use Strukt\Router\Exception\ValidationException;
use Strukt\Router\Middleware\Authorization as GateMiddleware;

/**
 * Dispatches a request through the router to a response.
 *
 * Dispatch stages run as a pipeline: match the route, run the middleware
 * chain plus any route gates, invoke the action, and wrap its result. Matched
 * request and response objects are carried by reference, so middleware and the
 * action observe the same objects; exceptions are mapped to HTTP error
 * responses instead of escaping the kernel.
 */
final class Kernel
{
    /**
     * @param Router $router Router being dispatched.
     */
    public function __construct(private Router $router)
    {
    }

    /**
     * Dispatches a request and returns the response without emitting it.
     *
     * @param RequestInterface|null $request Request to dispatch, or the globals.
     * @param ResponseInterface|null $response Response to build on, or a new one.
     * @return ResponseInterface Resulting response.
     */
    public function handle(?RequestInterface $request = null, ?ResponseInterface $response = null): ResponseInterface
    {
        $request ??= $this->resolveGlobals();
        $response ??= new Response();

        try {
            $match = $this->router->match($request->path(), $request->method());

            return $this->dispatch($match['route'], $match['params'], $request, $response);
        } catch (\Throwable $e) {
            return $this->error($e);
        }
    }

    /**
     * Dispatches a request and emits the response.
     *
     * @param RequestInterface|null $request Request to dispatch, or the globals.
     * @return void
     */
    public function send(?RequestInterface $request = null): void
    {
        $this->handle($request)->send();
    }

    /**
     * Runs the middleware chain and the action for a matched route.
     *
     * @param Route $route Matched route.
     * @param array<string, mixed> $params Matched parameter values.
     * @param RequestInterface $request Incoming request.
     * @param ResponseInterface $response Response under construction.
     * @return ResponseInterface Resulting response.
     */
    private function dispatch(
        Route $route,
        array $params,
        RequestInterface $request,
        ResponseInterface $response,
    ): ResponseInterface {
        $services = $this->router->services();

        $steps = array_map(
            fn (object|callable|string $definition): MiddlewareInterface => $this->router->makeMiddleware($definition, $services),
            [...$this->router->middlewareList(), ...$route->getMiddleware()],
        );

        $user = $this->resolveUser($route, $services);
        if ($user !== null) {
            $request->setUser($user);
        }

        if ($route->authRequired() && $user === null) {
            throw new AuthorizationException('Authentication required!', 401);
        }

        if ($route->getPermissions() !== []) {
            $steps[] = new GateMiddleware($services['permissions'], $route->getPermissions());
        }

        $response = $this->run($steps, $request, $response);

        $result = (new Invoker($this->router))->invoke($route, $params, $request, $response, $services);

        return $this->wrap($result, $response);
    }

    /**
     * Composes the middleware steps into a single callable via recursion.
     *
     * @param list<callable|MiddlewareInterface> $steps Remaining steps.
     * @param RequestInterface $request Incoming request.
     * @param ResponseInterface $response Response under construction.
     * @return ResponseInterface Response produced by the remaining chain.
     */
    private function run(array $steps, RequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $step = array_shift($steps);
        if ($step === null) {
            return $response;
        }

        $next = fn (RequestInterface $request, ResponseInterface $response): ResponseInterface => $this->run($steps, $request, $response);

        if ($step instanceof MiddlewareInterface) {
            return $step->process($request, $response, $next);
        }

        return $step($request, $response, $next);
    }

    /**
     * Resolves the current user for a route carrying auth requirements.
     *
     * @param Route $route Matched route.
     * @param array<string, mixed> $services Per-request service map.
     * @return UserInterface|null Current user, or null for a guest.
     */
    private function resolveUser(Route $route, array $services): ?UserInterface
    {
        if (!$route->authRequired() && $route->getPermissions() === []) {
            return null;
        }

        $verify = $services['verify'] ?? null;

        return $verify !== null
            ? $verify($services['session'])
            : $services['auth']->user();
    }

    /**
     * Converts an action result into the response.
     *
     * @param mixed $result Action result.
     * @param ResponseInterface $response Response under construction.
     * @return ResponseInterface Resulting response.
     *
     * @throws ResolutionException When the result is unsupported.
     */
    private function wrap(mixed $result, ResponseInterface $response): ResponseInterface
    {
        if ($result instanceof ResponseInterface) {
            return $result;
        }

        if ($result instanceof DownloadInterface) {
            return $response
                ->setHeader('Content-Type', $result->mime())
                ->setHeader('Content-Length', (string) $result->size())
                ->setHeader('Content-Disposition', $result->toHeaderValue())
                ->setBody($result->content());
        }

        if (is_string($result)) {
            return $response->setBody($result);
        }

        if (is_array($result)) {
            return $response->setJson($result);
        }

        if ($result === null) {
            return $response->setBody('');
        }

        throw new ResolutionException('Route actions must return a string, an array, or a ResponseInterface.');
    }

    /**
     * Builds the globals request through the router factory or PHP globals.
     *
     * @return RequestInterface Incoming request.
     */
    private function resolveGlobals(): RequestInterface
    {
        $factory = $this->router->globalsFactory();

        return $factory !== null ? $factory() : Request::createFromGlobals();
    }

    /**
     * Maps a thrown error to an HTTP response.
     *
     * @param \Throwable $error Caught error.
     * @return ResponseInterface Error response matching the exception.
     */
    private function error(\Throwable $error): ResponseInterface
    {
        $response = new Response();

        if ($error instanceof MethodNotAllowedException) {
            return $response
                ->setStatus(405)
                ->setHeader('Allow', implode(', ', $error->allowedMethods()))
                ->setBody($error->getMessage());
        }

        if ($error instanceof ValidationException) {
            return $response
                ->setStatus(422)
                ->setBody(json_encode(['errors' => $error->errors()], JSON_THROW_ON_ERROR))
                ->setHeader('Content-Type', 'application/json');
        }

        $code = $error instanceof RouteNotFoundException
            || $error instanceof AuthorizationException
            || $error instanceof ResolutionException
            || $error instanceof ValidationException
            || $error instanceof MethodNotAllowedException
            ? $error->getCode()
            : 500;

        if ($code < 100 || $code > 599) {
            $code = 500;
        }

        return $response->setStatus($code)->setBody($error->getMessage());
    }
}