<?php

use Strukt\Http\Download;
use Strukt\Http\Request;
use Strukt\Http\Response;
use Strukt\Http\ResponseBuilder;
use Strukt\Http\UploadedFile;
use Strukt\User;

it('reads the path without the query string', function () {
    $request = new Request('GET', '/users?page=2');

    expect($request->path())->toBe('/users');
    expect($request->uri())->toBe('/users?page=2');
});

it('gives body values precedence over query values', function () {
    $request = new Request('POST', '/search?q=query', ['q' => 'query'], ['q' => 'body']);

    expect($request->input('q'))->toBe('body');
    expect($request->query('q'))->toBe('query');
});

it('merges query and body values in all()', function () {
    $request = new Request('POST', '/x?a=1', ['a' => '1'], ['b' => '2']);

    expect($request->all())->toBe(['a' => '1', 'b' => '2']);
});

it('reads headers case-insensitively', function () {
    $request = new Request('GET', '/', [], [], ['X-Token' => 'abc']);

    expect($request->header('x-token'))->toBe('abc');
    expect($request->header('X-Missing', 'fallback'))->toBe('fallback');
    expect($request->hasHeader('X-Token'))->toBeTrue();
});

it('answers method checks case-insensitively', function () {
    $request = new Request('get', '/');

    expect($request->isMethod('GET'))->toBeTrue();
    expect($request->isMethod('post'))->toBeFalse();
});

it('returns null when no uploaded file is present', function () {
    expect((new Request('POST', '/'))->file('avatar'))->toBeNull();
});

it('binds a user to the request', function () {
    $request = new Request('GET', '/');
    $user = new User(email: 'ada@example.com');

    expect($request->user())->toBeNull();

    $request->setUser($user);

    expect($request->user())->toBe($user);
});

it('builds a response chainably', function () {
    $response = (new Response())
        ->setStatus(201)
        ->setHeader('X-First', '1')
        ->addHeader('X-First', '2')
        ->setBody('created');

    expect($response->status())->toBe(201);
    expect($response->header('X-First'))->toBe('1, 2');
    expect($response->header('X-None', 'none'))->toBe('none');
    expect($response->body())->toBe('created');
});

it('encodes JSON responses', function () {
    $response = (new Response())->setJson(['ok' => true]);

    expect($response->header('Content-Type'))->toBe('application/json');
    expect(json_decode($response->body(), true))->toBe(['ok' => true]);
});

it('rejects an out-of-range status code', function () {
    (new Response())->setStatus(999);
})->throws(InvalidArgumentException::class);

it('emits only the body through send()', function () {
    ob_start();
    (new Response())->setBody('hello')->send();
    $output = ob_get_clean();

    expect($output)->toBe('hello');
});

it('builds plain responses with the text content type', function () {
    $response = (new ResponseBuilder(200, ['X-Tag' => 'a']))->body('plain');

    expect($response->header('Content-Type'))->toBe('text/plain; charset=utf-8');
    expect($response->body())->toBe('plain');
    expect($response->header('X-Tag'))->toBe('a');
});

it('builds redirect responses', function () {
    $response = (new ResponseBuilder())->redirect('/home', 301);

    expect($response->status())->toBe(301);
    expect($response->header('Location'))->toBe('/home');
});

it('serves a temporary file through download', function () {
    $path = tempnam(sys_get_temp_dir(), 'router_');
    file_put_contents($path, 'payload');

    $download = new Download($path, 'report.txt');

    expect($download->filename())->toBe('report.txt');
    expect($download->mime())->toBe('text/plain');
    expect($download->size())->toBe(strlen('payload'));
    expect($download->content())->toBe('payload');
    expect($download->inline()->disposition())->toBe('inline');

    ob_start();
    $download->send();
    $output = ob_get_clean();

    expect($output)->toBe('payload');

    unlink($path);
});

it('describes an uploaded file from its metadata', function () {
    $file = new UploadedFile([
        'name' => 'photo.png',
        'type' => 'image/png',
        'size' => 10,
        'error' => UPLOAD_ERR_OK,
        'tmp_name' => '',
    ]);

    expect($file->name())->toBe('photo.png');
    expect($file->extension())->toBe('png');
    expect($file->mime())->toBe('image/png');
    expect($file->size())->toBe(10);
    expect($file->isValid())->toBeFalse();
});