<?php

require __DIR__.'/../vendor/autoload.php';

// The package ships global helpers that proxy to the same classes. They
// register themselves once through the strukt4 commons helper registry.

// matcher() hands out a standalone UrlMatcher.
$m = matcher(['/user/{id:int}']);
var_export($m->params('/user/{id:int}', '/user/9'));
echo "\n";

// request() builds a Request, response() a fluent builder.
$req = request(['q' => 'strukt'], 'GET', '/search?q=strukt');
echo $req->path().' q='.$req->input('q')."\n";

// router() returns a shared instance; routes register on it.
$r = router();
$r->get('/hello', fn () => 'hi from helper')->name('hi');
$r->get('/json', fn () => response()->json(['ok' => true]));
$r->get('/away', fn () => redirect('/hello', 302));

// route() dispatches immediately and returns the response.
$hi = route('/hello', 'GET');
printf("route('/hello')  -> %d  %s\n", $hi->status(), $hi->body());

$json = route('/json', 'GET');
printf("route('/json')   -> %d  %s\n", $json->status(), $json->body());

$away = route('/away', 'GET');
printf("route('/away')   -> %d  Location: %s\n", $away->status(), $away->header('Location'));

// download() produces a file response with the storage filename.
$tmp = tempnam(sys_get_temp_dir(), 'dl');
file_put_contents($tmp, 'attachment');
$file = download($tmp, 'notes.txt');
echo get_class($file).' -> filesize '.filesize($tmp)."\n";