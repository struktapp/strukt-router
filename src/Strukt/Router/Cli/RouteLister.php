<?php

namespace Strukt\Router\Cli;

use Strukt\Router\Route;
use Strukt\Router\Router;

/**
 * Lists the routes registered on a router as an aligned terminal table.
 *
 * The lister reads the collection in registration order and renders one row
 * per route: the accepting methods, the pattern, the name, and the gates the
 * route carries. It is the data source behind the `strukt-routes` binary.
 */
final class RouteLister
{
    /**
     * @param Router $router Router whose routes are listed.
     */
    public function __construct(private Router $router)
    {
    }

    /**
     * Loads a routes bootstrap file and returns the router it declares.
     *
     * The file is included, not required-once, so every call builds a fresh
     * router. It must return the router it registered its routes on.
     *
     * @param string $file Path to the routes file.
     * @return Router Router declared by the file.
     *
     * @throws \InvalidArgumentException When the file is missing or returns something else.
     */
    public static function load(string $file): Router
    {
        if (!is_file($file)) {
            throw new \InvalidArgumentException("Routes file [{$file}] was not found.");
        }

        $router = require $file;

        if (!$router instanceof Router) {
            throw new \InvalidArgumentException(
                sprintf("Routes file [%s] must return a %s instance.", $file, Router::class)
            );
        }

        return $router;
    }

    /**
     * Builds one display row per registered route.
     *
     * @return list<array{methods: string, pattern: string, name: string, auth: string}> Rows in registration order.
     */
    public function rows(): array
    {
        $rows = [];

        foreach ($this->router->collection()->all() as $route) {
            $rows[] = [
                'methods' => implode(', ', $route->methods()),
                'pattern' => $route->pattern(),
                'name' => $route->getName() ?? '-',
                'auth' => $this->gates($route),
            ];
        }

        return $rows;
    }

    /**
     * Renders the table with a header, a rule, and one line per route.
     *
     * Columns are padded to the widest cell and left aligned; nothing is
     * padded after the last column.
     *
     * @return string Table ending in a newline, or the empty-collection message.
     */
    public function render(): string
    {
        $rows = $this->rows();

        if ($rows === []) {
            return "No routes registered.\n";
        }

        $headers = ['METHODS', 'PATTERN', 'NAME', 'AUTH'];
        $widths = array_map('strlen', $headers);

        foreach ($rows as $row) {
            foreach (array_values($row) as $index => $cell) {
                $widths[$index] = max($widths[$index], strlen($cell));
            }
        }

        $line = static function (array $cells) use ($widths): string {
            $padded = [];
            foreach (array_values($cells) as $index => $cell) {
                $padded[] = str_pad($cell, $widths[$index]);
            }

            return rtrim(implode('  ', $padded))."\n";
        };

        $table = $line($headers)
            .$line(array_map(static fn (int $width): string => str_repeat('-', $width), $widths));

        foreach ($rows as $row) {
            $table .= $line($row);
        }

        return $table;
    }

    /**
     * Describes the gates a route carries: authentication and permissions.
     *
     * @param Route $route Route to describe.
     * @return string `auth`, `perm: …`, both joined, or `-` for an open route.
     */
    private function gates(Route $route): string
    {
        $gates = [];

        if ($route->authRequired()) {
            $gates[] = 'auth';
        }

        if ($route->getPermissions() !== []) {
            $gates[] = 'perm: '.implode(', ', $route->getPermissions());
        }

        return $gates === [] ? '-' : implode(', ', $gates);
    }
}
