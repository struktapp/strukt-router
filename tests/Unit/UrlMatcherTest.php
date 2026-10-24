<?php

use Strukt\Router\UrlMatcher;

beforeEach(function () {
    $this->matcher = new UrlMatcher();
});

it('matches a plain placeholder', function () {
    $params = $this->matcher->params('/hello/{name}', '/hello/world');

    expect($params)->toBe(['name' => 'world']);
});

it('casts a typed integer placeholder', function () {
    $params = $this->matcher->params('/user/{id:int}', '/user/7');

    expect($params)->toBe(['id' => 7]);
});

it('casts a typed float placeholder', function () {
    $params = $this->matcher->params('/price/{amount:float}', '/price/12.50');

    expect($params)->toBe(['amount' => 12.5]);
});

it('casts a typed boolean placeholder', function () {
    $params = $this->matcher->params('/flag/{on:bool}', '/flag/true');

    expect($params)->toBe(['on' => true]);
});

it('rejects a typed placeholder when the value does not conform', function () {
    expect($this->matcher->params('/user/{id:int}', '/user/abc'))->toBeNull();
});

it('matches an alpha placeholder', function () {
    $params = $this->matcher->params('/name/{name:alpha}', '/name/Ada');

    expect($params)->toBe(['name' => 'Ada']);
});

it('matches a slug placeholder', function () {
    $params = $this->matcher->params('/post/{slug}', '/post/hello-world');

    expect($params)->toBe(['slug' => 'hello-world']);
});

it('matches a date placeholder', function () {
    $params = $this->matcher->params('/events/{on:date}', '/events/2026-10-01');

    expect($params)->toBe(['on' => '2026-10-01']);
});

it('matches a uuid placeholder', function () {
    $uuid = '550e8400-e29b-41d4-a716-446655440000';

    expect($this->matcher->params('/tokens/{id:uuid}', "/tokens/{$uuid}"))->toBe(['id' => $uuid]);
});

it('matches the root path', function () {
    expect($this->matcher->params('/', '/'))->toBe([]);
});

it('matches a catch-all placeholder across segments', function () {
    $params = $this->matcher->params('/files/{rest:*}', '/files/a/b/c.txt');

    expect($params)->toBe(['rest' => 'a/b/c.txt']);
});

it('matches an optional placeholder when present and absent', function () {
    $pattern = '/blog/{year:int}/{?month:int}';

    expect($this->matcher->params($pattern, '/blog/2026'))->toBe(['year' => 2026]);
    expect($this->matcher->params($pattern, '/blog/2026/02'))->toBe(['year' => 2026, 'month' => 2]);
});

it('returns null when the path does not match', function () {
    expect($this->matcher->params('/hello/{name}', '/goodbye'))->toBeNull();
});

it('reports the first matching pattern through which()', function () {
    $matcher = new UrlMatcher(['/user/{id:int}', '/user/{slug}']);

    expect($matcher->which('/user/7'))->toBe('/user/{id:int}');
    expect($matcher->lastParams())->toBe(['id' => 7]);
});

it('returns null from which() when no pattern matches', function () {
    $matcher = new UrlMatcher(['/user/{id:int}']);

    expect($matcher->which('/admin'))->toBeNull();
    expect($matcher->lastParams())->toBe([]);
});

it('reconstructs a URI from a pattern and params', function () {
    expect((new UrlMatcher())->uri('/user/{id:int}', ['id' => 7]))->toBe('/user/7');
    expect((new UrlMatcher())->uri('/blog/{year:int}/{?month:int}', ['year' => 2026]))->toBe('/blog/2026');
});

it('throws when a required parameter is missing for reverse generation', function () {
    (new UrlMatcher())->uri('/user/{id:int}');
})->throws(InvalidArgumentException::class);

it('throws when an unsupported placeholder type is used', function () {
    (new UrlMatcher())->params('/x/{v:custom}', '/x/1');
})->throws(InvalidArgumentException::class);