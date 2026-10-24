<?php

use Strukt\Cache\Driver\Fs;
use Strukt\Cache\Driver\MemCached;
use Strukt\Cache\Driver\Memory;
use Strukt\Fs\Fs as FileSystem;
use Strukt\Http\Session\Cache;

beforeEach(function () {
    Memory::reset();
});

it('persists values through the driver across instances', function () {
    (new Cache(new Memory('sess')))->set('user.id', 7);

    $restored = new Cache(new Memory('sess'));

    expect($restored->get('user.id'))->toBe(7)
        ->and($restored->get('missing', 'fallback'))->toBe('fallback')
        ->and($restored->has('user.id'))->toBeTrue();
});

it('reads values already stored in the namespace', function () {
    (new Memory('sess'))->put('session', ['values' => ['token' => 'abc'], 'flashes' => []]);

    expect((new Cache(new Memory('sess')))->get('token'))->toBe('abc');
});

it('keeps dotted session keys intact', function () {
    (new Cache(new Memory('sess')))->set('user.profile.name', 'Ada');

    $restored = new Cache(new Memory('sess'));

    expect($restored->get('user.profile.name'))->toBe('Ada')
        ->and($restored->all())->toBe(['user.profile.name' => 'Ada']);
});

it('removes values and keeps flashes on clear', function () {
    $session = new Cache(new Memory('sess'));
    $session->set('a', 1)->flash('notice', 'hi');
    $session->remove('a');

    expect((new Cache(new Memory('sess')))->has('a'))->toBeFalse();

    $session->set('b', 2)->clear();

    $restored = new Cache(new Memory('sess'));

    expect($restored->all())->toBe([])
        ->and($restored->getFlash('notice'))->toBe('hi');
});

it('persists flashes across instances and consumes them on read', function () {
    (new Cache(new Memory('sess')))->flash('status', 'saved');

    expect((new Cache(new Memory('sess')))->getFlash('status'))->toBe('saved')
        ->and((new Cache(new Memory('sess')))->getFlash('status', 'gone'))->toBe('gone');
});

it('pulls every flash at once', function () {
    (new Cache(new Memory('sess')))->flash('a', 1)->flash('b', 2);

    expect((new Cache(new Memory('sess')))->pullFlash())->toBe(['a' => 1, 'b' => 2])
        ->and((new Cache(new Memory('sess')))->pullFlash())->toBe([]);
});

it('excludes flashes from all()', function () {
    $session = new Cache(new Memory('sess'));
    $session->set('user', 1)->flash('notice', 'hi');

    expect($session->all())->toBe(['user' => 1]);
});

it('isolates clients by driver namespace', function () {
    (new Cache(new Memory('sess-a')))->set('user.id', 1);
    (new Cache(new Memory('sess-b')))->set('user.id', 2);

    expect((new Cache(new Memory('sess-a')))->get('user.id'))->toBe(1)
        ->and((new Cache(new Memory('sess-b')))->get('user.id'))->toBe(2);
});

it('persists through the filesystem driver across instances', function () {
    $directory = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'strukt-session-' . bin2hex(random_bytes(6));

    try {
        (new Cache(new Fs('sess', $directory)))->set('user.id', 7)->flash('notice', 'hi');

        $restored = new Cache(new Fs('sess', $directory));

        expect($restored->get('user.id'))->toBe(7)
            ->and($restored->getFlash('notice'))->toBe('hi');
    } finally {
        FileSystem::rmdir($directory);
    }
});

it('persists through the memcached driver across connections', function () {
    if (!class_exists(\Memcached::class)) {
        $this->markTestSkipped('The ext-memcached extension is not available.');
    }

    $name = 'strukt-session-' . bin2hex(random_bytes(6));

    try {
        $session = new Cache(new MemCached($name));
    } catch (RuntimeException $exception) {
        $this->markTestSkipped('No Memcached server available: ' . $exception->getMessage());
    }

    try {
        $session->set('user.id', 7)->flash('notice', 'hi');

        $restored = new Cache(new MemCached($name));

        expect($restored->get('user.id'))->toBe(7)
            ->and($restored->getFlash('notice'))->toBe('hi');
    } finally {
        (new MemCached($name))->remove('session')->save();
    }
});
