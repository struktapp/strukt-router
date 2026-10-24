<?php

use Strukt\Contract\Http\ResponseInterface;
use Strukt\Http\Download;
use Strukt\Http\Request;
use Strukt\Http\ResponseBuilder;
use Strukt\Router\Router;
use Strukt\Router\UrlMatcher;

if (function_exists('helper')) {
    helper('router');
}

if (!function_exists('router') && function_exists('helper_add') && helper_add('router')) {
    function router(): Router
    {
        return reg('router') ?? reg('router', new Router(function_exists('app') ? app() : null));
    }
}

if (!function_exists('request') && function_exists('helper_add') && helper_add('router_request')) {
    function request(array $input = [], ?string $method = null, ?string $uri = null, ?array $headers = null): Request
    {
        $method ??= strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
        $uri ??= $_SERVER['REQUEST_URI'] ?? '/';

        return new Request($method, $uri, [], $input, $headers ?? []);
    }
}

if (!function_exists('response') && function_exists('helper_add') && helper_add('router_response')) {
    function response(int $code = 200, array $headers = []): ResponseBuilder
    {
        return new ResponseBuilder($code, $headers);
    }
}

if (!function_exists('route') && function_exists('helper_add') && helper_add('router_route')) {
    function route(string $path, string $method = 'GET', array $input = [], ?array $headers = null): ResponseInterface
    {
        return router()->kernel()->handle(request($input, $method, $path, $headers));
    }
}

if (!function_exists('matcher') && function_exists('helper_add') && helper_add('router_matcher')) {
    function matcher(array $patterns = []): UrlMatcher
    {
        return new UrlMatcher($patterns);
    }
}

if (!function_exists('redirect') && function_exists('helper_add') && helper_add('router_redirect')) {
    function redirect(string $url, int $status = 302): ResponseInterface
    {
        return response($status)->redirect($url);
    }
}

if (!function_exists('download') && function_exists('helper_add') && helper_add('router_download')) {
    function download(string $path, ?string $filename = null): Download
    {
        return new Download($path, $filename);
    }
}