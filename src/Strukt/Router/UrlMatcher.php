<?php

namespace Strukt\Router;

/**
 * Compiles route patterns into regular expressions and extracts parameters.
 *
 * A pattern is written as slash separated segments. A segment may be literal,
 * a plain placeholder `{name}`, a typed placeholder `{name:type}`, a wildcard
 * `{rest:*}`, or an optional placeholder `{?page:int}`:
 *
 * ```php
 * /user/{id:int}
 * /hello/{name}
 * /files/{rest:*}
 * /blog/{year:int}/{?month:int}
 * ```
 *
 * Compilation is split into normalize, segment, and assemble stages. Typed
 * values are cast on extraction, so `{id:int}` yields an int in the action.
 * Reverse generation turns a pattern back into a URI from a parameter map.
 */
class UrlMatcher
{
    /**
     * Supported placeholder types mapped to their match fragments.
     *
     * @var array<string, string>
     */
    private const TYPES = [
        'int' => '[0-9]+',
        'float' => '[+-]?(?:[0-9]+(?:\.[0-9]+)?)',
        'bool' => 'true|false',
        'alpha' => '[A-Za-z]+',
        'alphanum' => '[A-Za-z0-9_-]+',
        'slug' => '[a-z0-9]+(?:-[a-z0-9]+)*',
        'date' => '[0-9]{4}-[0-9]{2}-[0-9]{2}',
        'uuid' => '[0-9a-fA-F]{8}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{12}',
    ];

    /**
     * Params held from the most recent {@see which()} call.
     *
     * @var array<string, mixed>
     */
    private array $last = [];

    /**
     * @param list<string> $patterns Route patterns to match against.
     */
    public function __construct(private array $patterns = [])
    {
    }

    /**
     * Compiles a route pattern into a regex and its parameter definitions.
     *
     * @param string $pattern Route pattern.
     * @return array{regex: string, keys: list<array{name: string, type: ?string, optional: bool, catch: bool}>, parts: list<array<string, mixed>>} Compiled definition.
     */
    public function compile(string $pattern): array
    {
        $pattern = trim($pattern) === '/' ? '/' : '/' . trim(trim($pattern), '/');

        $segments = $pattern === '/'
            ? []
            : (preg_split('#/+#', $pattern, -1, PREG_SPLIT_NO_EMPTY) ?: []);

        return $this->assemble(array_map([$this, 'part'], $segments));
    }

    /**
     * Checks whether a path matches a pattern.
     *
     * @param string $pattern Route pattern.
     * @param string $path Request path.
     * @return bool True when the path matches.
     */
    public function isMatch(string $pattern, string $path): bool
    {
        return $this->params($pattern, $path) !== null;
    }

    /**
     * Extracts typed parameters from a path against a pattern.
     *
     * @param string $pattern Route pattern.
     * @param string $path Request path.
     * @return array<string, mixed>|null Param values, or null when the path does not match.
     */
    public function params(string $pattern, string $path): ?array
    {
        $compiled = $this->compile($pattern);

        if (!preg_match($compiled['regex'], $path, $matches)) {
            return null;
        }

        $params = [];
        foreach ($compiled['keys'] as $key) {
            if (!isset($matches[$key['name']])) {
                continue;
            }

            $params[$key['name']] = $this->cast($key['type'], $matches[$key['name']]);
        }

        return $params;
    }

    /**
     * Returns the first pattern matching the path and remembers its params.
     *
     * @param string $path Request path.
     * @return string|null Matching pattern, or null when none match.
     */
    public function which(string $path): ?string
    {
        foreach ($this->patterns as $pattern) {
            $params = $this->params($pattern, $path);
            if ($params !== null) {
                $this->last = $params;

                return $pattern;
            }
        }

        $this->last = [];

        return null;
    }

    /**
     * Reads the params of the most recent {@see which()} call.
     *
     * @return array<string, mixed> Param values from the last match.
     */
    public function lastParams(): array
    {
        return $this->last ?? [];
    }

    /**
     * Reconstructs a URI from a pattern and a parameter map.
     *
     * Optional placeholders are omitted when their value is absent. A required
     * placeholder without a value throws.
     *
     * @param string $pattern Route pattern.
     * @param array<string, mixed> $params Parameter values keyed by placeholder name.
     * @return string URI, such as `/user/7`.
     *
     * @throws \InvalidArgumentException When a required parameter is missing.
     */
    public function uri(string $pattern, array $params = []): string
    {
        $compiled = $this->compile($pattern);

        $uri = '';
        foreach ($compiled['parts'] as $part) {
            $name = $part['name'];

            if ($name === null) {
                $uri .= '/' . $part['token'];

                continue;
            }

            if (array_key_exists($name, $params)) {
                $uri .= '/' . (string) ($params[$name] ?? '');

                continue;
            }

            if ($part['optional']) {
                continue;
            }

            if ($part['catch']) {
                $uri .= '/';

                continue;
            }

            throw new \InvalidArgumentException("Missing required parameter [{$name}] for route [{$pattern}].");
        }

        return $uri === '' ? '/' : $uri;
    }

    /**
     * Describes one segment of a pattern.
     *
     * @param string $segment Raw segment such as `{id:int}`.
     * @return array<string, mixed> Part description.
     */
    private function part(string $segment): array
    {
        if (!str_starts_with($segment, '{') || !str_ends_with($segment, '}')) {
            return [
                'regex' => preg_quote($segment, '#'),
                'name' => null,
                'token' => $segment,
                'optional' => false,
                'catch' => false,
                'type' => null,
            ];
        }

        $inner = substr($segment, 1, -1);
        $optional = str_starts_with($inner, '?') ? true : false;
        if ($optional) {
            $inner = substr($inner, 1);
        }

        $type = null;
        if (str_contains($inner, ':')) {
            [$name, $type] = explode(':', $inner, 2);
        } else {
            $name = $inner;
        }

        $catch = $type === '*';
        if (!$catch && $type !== null && !array_key_exists($type, self::TYPES)) {
            throw new \InvalidArgumentException("Unsupported parameter type [{$type}] in route segment [{$segment}].");
        }

        $fragment = $catch
            ? '(?<' . $name . '>.*)'
            : '(?<' . $name . '>' . ($type === null ? '[^/]+' : self::TYPES[$type]) . ')';

        return [
            'regex' => $fragment,
            'name' => $name,
            'token' => $segment,
            'optional' => $optional,
            'catch' => $catch,
            'type' => $type,
            'segment' => $segment,
        ];
    }

    /**
     * Joins the parts into a compiled definition.
     *
     * @param list<array<string, mixed>> $parts Part descriptions.
     * @return array{regex: string, keys: list<array{name: string, type: ?string, optional: bool, catch: bool}>, parts: list<array<string, mixed>>} Compiled definition.
     */
    private function assemble(array $parts): array
    {
        if ($parts === []) {
            return ['regex' => '#^/$#', 'keys' => [], 'parts' => $parts];
        }

        $regex = '';
        $keys = [];
        foreach ($parts as $part) {
            if ($part['name'] !== null) {
                $keys[] = [
                    'name' => $part['name'],
                    'type' => $part['type'],
                    'optional' => $part['optional'],
                    'catch' => $part['catch'],
                ];
            }

            $regex .= $part['optional']
                ? '(?:/' . $part['regex'] . ')?'
                : '/' . $part['regex'];
        }

        return ['regex' => '#^' . $regex . '$#', 'keys' => $keys, 'parts' => $parts];
    }

    /**
     * Casts a captured value to its declared type.
     *
     * @param string|null $type Placeholder type.
     * @param string $value Captured text.
     * @return mixed Cast value.
     */
    private function cast(?string $type, string $value): mixed
    {
        return match ($type) {
            'int' => (int) $value,
            'float' => (float) $value,
            'bool' => filter_var($value, FILTER_VALIDATE_BOOL),
            default => (string) $value,
        };
    }
}