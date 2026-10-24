<?php

use Strukt\Http\Session\Native;

beforeEach(function () {
    $_SESSION = [];
});

afterEach(function () {
    $_SESSION = [];
});

it('persists values in the native session', function () {
    $session = new Native();

    $session->set('user.id', 7);

    expect($_SESSION['user.id'])->toBe(7);
    expect($session->get('user.id'))->toBe(7);
    expect($session->get('missing', 'fallback'))->toBe('fallback');
    expect($session->has('user.id'))->toBeTrue();
});

it('reads values written to the native session directly', function () {
    $_SESSION['token'] = 'abc';

    expect((new Native())->get('token'))->toBe('abc');
});

it('removes and clears values', function () {
    $_SESSION = ['a' => 1, 'b' => 2];

    $session = new Native();
    $session->remove('a');
    expect($_SESSION)->toBe(['b' => 2]);

    $session->clear();
    expect($_SESSION)->toBe([]);
});

it('persists flashes across instances and consumes them on read', function () {
    (new Native())->flash('status', 'saved');

    expect((new Native())->getFlash('status'))->toBe('saved');
    expect((new Native())->getFlash('status', 'gone'))->toBe('gone');
});

it('pulls every flash at once', function () {
    (new Native())->flash('a', 1)->flash('b', 2);

    expect((new Native())->pullFlash())->toBe(['a' => 1, 'b' => 2]);
    expect((new Native())->pullFlash())->toBe([]);
});

it('excludes flashes from all()', function () {
    $session = new Native();
    $session->set('user', 1)->flash('notice', 'hi');

    expect($session->all())->toBe(['user' => 1]);
});

it('does not start a session under the CLI', function () {
    new Native();

    expect(session_status())->not->toBe(PHP_SESSION_ACTIVE);
});
