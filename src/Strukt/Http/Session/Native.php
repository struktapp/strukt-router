<?php

namespace Strukt\Http\Session;

use Strukt\Session;

/**
 * Native PHP session store backed by `$_SESSION`.
 *
 * {@see \Strukt\Session} keeps its values in memory, so a store built from
 * `new Session($_SESSION)` would drop every write at the end of the request.
 * This subclass reads and writes `$_SESSION` directly — starting the native
 * session on construction under a web SAPI — which lets {@see \Strukt\Auth}
 * persist a user across requests.
 *
 * Values and flashes both persist. Flashes are kept under a reserved key and
 * are cleared as they are read, so they survive a redirect and last exactly one
 * request. Under the CLI the constructor does not start a session, which keeps
 * tests and console tooling free of session side effects.
 */
class Native extends Session
{
    /** Reserved `$_SESSION` key holding the flash values. */
    private const FLASH_KEY = '__strukt_flashes';

    public function __construct()
    {
        if (session_status() !== PHP_SESSION_ACTIVE && PHP_SAPI !== 'cli') {
            @session_start();
        }

        if (!isset($_SESSION) || !is_array($_SESSION)) {
            $_SESSION = [];
        }
    }

    /**
     * {@inheritDoc}
     */
    public function get(string $key, mixed $default = null): mixed
    {
        return array_key_exists($key, $_SESSION) ? $_SESSION[$key] : $default;
    }

    /**
     * {@inheritDoc}
     */
    public function set(string $key, mixed $value = null): static
    {
        $_SESSION[$key] = $value;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function has(string $key): bool
    {
        return array_key_exists($key, $_SESSION);
    }

    /**
     * {@inheritDoc}
     */
    public function remove(string $key): static
    {
        unset($_SESSION[$key]);

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function clear(): static
    {
        $_SESSION = [];

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function all(): array
    {
        $values = $_SESSION;
        unset($values[self::FLASH_KEY]);

        return $values;
    }

    /**
     * {@inheritDoc}
     */
    public function flash(string $key, mixed $value = null): static
    {
        $_SESSION[self::FLASH_KEY][$key] = $value;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function getFlash(string $key, mixed $default = null): mixed
    {
        if (!isset($_SESSION[self::FLASH_KEY]) || !array_key_exists($key, $_SESSION[self::FLASH_KEY])) {
            return $default;
        }

        $value = $_SESSION[self::FLASH_KEY][$key];
        unset($_SESSION[self::FLASH_KEY][$key]);

        return $value;
    }

    /**
     * {@inheritDoc}
     */
    public function pullFlash(): array
    {
        $flashes = $_SESSION[self::FLASH_KEY] ?? [];
        unset($_SESSION[self::FLASH_KEY]);

        return $flashes;
    }
}
