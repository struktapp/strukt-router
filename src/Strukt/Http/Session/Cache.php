<?php

namespace Strukt\Http\Session;

use Strukt\Contract\CacheDriverInterface;
use Strukt\Session;

/**
 * Cache-backed session store.
 *
 * Persists the whole session — values and flashes — through a
 * {@see CacheDriverInterface} driver, so it can outlive the request and be
 * shared across processes (memcached, filesystem, memory) instead of living
 * only in `$_SESSION` or in process memory.
 *
 * The driver instance doubles as the client key: every driver is a namespace,
 * so give each client its own (`memc('sess_<id>')`) to keep sessions isolated.
 * Values and flashes are stored together under one reserved key, which keeps
 * session keys that contain dots (`user.id`) from being mistaken for cache
 * paths. Mutations are written through to the driver immediately.
 */
class Cache extends Session
{
    /** Reserved driver key holding the values and flashes. */
    private const PAYLOAD_KEY = 'session';

    /**
     * @param CacheDriverInterface $driver Namespace backing this session.
     */
    public function __construct(private readonly CacheDriverInterface $driver)
    {
        $payload = $this->driver->get(self::PAYLOAD_KEY);

        parent::__construct($this->segment($payload, 'values'));
        $this->flashes = $this->segment($payload, 'flashes');
    }

    /**
     * {@inheritDoc}
     */
    public function set(string $key, mixed $value = null): static
    {
        parent::set($key, $value);

        return $this->persist();
    }

    /**
     * {@inheritDoc}
     */
    public function remove(string $key): static
    {
        parent::remove($key);

        return $this->persist();
    }

    /**
     * {@inheritDoc}
     */
    public function clear(): static
    {
        parent::clear();

        return $this->persist();
    }

    /**
     * {@inheritDoc}
     */
    public function flash(string $key, mixed $value = null): static
    {
        parent::flash($key, $value);

        return $this->persist();
    }

    /**
     * {@inheritDoc}
     */
    public function getFlash(string $key, mixed $default = null): mixed
    {
        if (!array_key_exists($key, $this->flashes)) {
            return $default;
        }

        $value = parent::getFlash($key, $default);
        $this->persist();

        return $value;
    }

    /**
     * {@inheritDoc}
     */
    public function pullFlash(): array
    {
        $flashes = parent::pullFlash();
        $this->persist();

        return $flashes;
    }

    /**
     * Writes the current values and flashes back to the driver.
     *
     * @return static This session.
     */
    private function persist(): static
    {
        $this->driver->put(self::PAYLOAD_KEY, [
            'values' => $this->values,
            'flashes' => $this->flashes,
        ]);
        $this->driver->save();

        return $this;
    }

    /**
     * Reads one array section from a stored payload.
     *
     * @param mixed $payload Stored payload, or null when absent.
     * @param string $key Section name.
     * @return array<string, mixed> Section values, or an empty array.
     */
    private function segment(mixed $payload, string $key): array
    {
        $section = is_array($payload) ? ($payload[$key] ?? null) : null;

        return is_array($section) ? $section : [];
    }
}
