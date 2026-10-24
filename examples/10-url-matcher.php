<?php

require __DIR__.'/../vendor/autoload.php';

use Strukt\Router\UrlMatcher;

// The UrlMatcher is the low-level engine behind route matching and reverse
// generation. It compiles a pattern into a regex, extracts typed params, and
// rebuilds URIs, and can match against a whole list of patterns.
$compiled = (new UrlMatcher())->compile('/user/{id:int}/posts/{?page:int}');

var_export($compiled['regex']);
echo "\n";

$matcher = new UrlMatcher(['/', '/user/{id:int}', '/blog/{year:int}/{?month:int}']);

// which() returns the first pattern accepting the path and keeps its params.
$pattern = $matcher->which('/user/7');
var_export([$pattern, $matcher->lastParams()]);
echo "\n";

// params() extracts and casts values for one pattern, or returns null.
var_export((new UrlMatcher())->params('/price/{value:float}', '/price/9.25'));
echo "\n";
var_export((new UrlMatcher())->params('/price/{value:float}', '/price/no'));
echo "\n";

// uri() rebuilds a URI, applying defaults and skipping optional placeholders.
$uri = (new UrlMatcher())->uri('/blog/{year:int}/{?month:int}', ['year' => 2026]);
echo "{$uri}\n";

// isMatch() is a convenient boolean probe.
var_export((new UrlMatcher())->isMatch('/files/{rest:*}', '/files/a/b/c'));
echo "\n";

// Typed values are cast, so {id:int} hands back an int, not a string.
$params = (new UrlMatcher())->params('/item/{id:int}/{on:bool}', '/item/3/true');
var_export($params);
echo "\n";

// A missing required parameter in uri() throws.
try {
    (new UrlMatcher())->uri('/user/{id:int}', []);
} catch (\InvalidArgumentException $e) {
    echo $e->getMessage()."\n";
}