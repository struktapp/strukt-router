<?php

namespace Strukt\Router;

use Strukt\Contract\AuthInterface;
use Strukt\Contract\FormInterface;
use Strukt\Contract\Http\RequestInterface;
use Strukt\Contract\Http\ResponseInterface;
use Strukt\Contract\Http\SessionInterface;
use Strukt\Ref;
use Strukt\Router\Exception\ResolutionException;
use Strukt\Router\Exception\ValidationException;

/**
 * Invokes a route action, resolving each parameter from the matched params,
 * the current request and response, and the configured services.
 *
 * Parameter resolution order for one argument:
 *  1. a matched route parameter of the same name,
 *  2. the request, response, session, or auth gate by interface,
 *  3. a validated route form by the form interface,
 *  4. a named binding, or a type bound on the router's container,
 *  5. a declared default, or a nullable parameter,
 *  6. otherwise the invocation fails with a resolution error.
 */
final class Invoker
{
    /**
     * @param Router $router Router supplying named bindings.
     */
    public function __construct(private Router $router)
    {
    }

    /**
     * Invokes the route action with fully resolved arguments.
     *
     * @param Route $route Matched route.
     * @param array<string, mixed> $params Matched parameter values.
     * @param RequestInterface $request Incoming request.
     * @param ResponseInterface $response Response under construction.
     * @param array<string, mixed> $services Per-request service map.
     * @return mixed Action result.
     *
     * @throws ResolutionException When an argument cannot be resolved.
     * @throws ValidationException When a route form fails validation.
     */
    public function invoke(
        Route $route,
        array $params,
        RequestInterface $request,
        ResponseInterface $response,
        array $services,
    ): mixed {
        $reference = Ref::func($route->action());
        $args = [];

        foreach ($reference->ref()->getParameters() as $parameter) {
            $name = $parameter->getName();
            $type = $parameter->getType();
            $typeName = $type instanceof \ReflectionNamedType ? $type->getName() : null;

            if (array_key_exists($name, $params)) {
                $args[] = $params[$name];

                continue;
            }

            if ($typeName === RequestInterface::class) {
                $args[] = $request;

                continue;
            }

            if ($typeName === ResponseInterface::class) {
                $args[] = $response;

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

            if ($typeName === FormInterface::class) {
                $args[] = $this->resolveForm($route, $request);

                continue;
            }

            if (array_key_exists($name, $this->router->bindings())) {
                $args[] = $this->router->resolve($name);

                continue;
            }

            if ($typeName !== null && $this->router->has($typeName)) {
                $args[] = $this->router->resolve($typeName);

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

            throw new ResolutionException(sprintf('Unable to resolve parameter [%s] of the route action.', $name));
        }

        return $reference->invoke(...$args);
    }

    /**
     * Submits the declared route form, validates it, and returns it.
     *
     * @param Route $route Matched route carrying the form class.
     * @param RequestInterface $request Incoming request supplying the values.
     * @return FormInterface Validated form.
     *
     * @throws ResolutionException When the route declares no form class.
     * @throws ValidationException When the form fails validation.
     */
    private function resolveForm(Route $route, RequestInterface $request): FormInterface
    {
        $class = $route->getForm();
        if (!is_string($class) || !class_exists($class)) {
            throw new ResolutionException('The route does not declare a form class to resolve.');
        }

        $form = new $class();
        $form->submit($request->all());

        if (!$form->validate()) {
            throw new ValidationException($form->errors());
        }

        return $form;
    }
}