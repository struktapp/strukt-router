<?php

namespace Strukt\Http;

use Strukt\Contract\Http\UploadedFileInterface;

/**
 * Uploaded file received through a multipart form submission.
 *
 * The class accepts a PHP `$_FILES` entry, which keeps it usable with any
 * server-side upload handling.
 */
final class UploadedFile implements UploadedFileInterface
{
    /**
     * @param array<string, mixed> $meta Upload metadata such as a `$_FILES` entry.
     */
    public function __construct(private array $meta = [])
    {
    }

    public function name(): string
    {
        return (string) ($this->meta['name'] ?? '');
    }

    public function size(): int
    {
        return (int) ($this->meta['size'] ?? 0);
    }

    public function mime(): string
    {
        $mime = $this->meta['type'] ?? '';
        if (is_string($mime) && $mime !== '') {
            return $mime;
        }

        return 'application/octet-stream';
    }

    public function extension(): string
    {
        return strtolower(pathinfo($this->name(), PATHINFO_EXTENSION));
    }

    public function error(): int
    {
        return (int) ($this->meta['error'] ?? UPLOAD_ERR_NO_FILE);
    }

    public function isValid(): bool
    {
        if ($this->error() !== UPLOAD_ERR_OK) {
            return false;
        }

        return is_file($this->path());
    }

    public function path(): string
    {
        return (string) ($this->meta['tmp_name'] ?? '');
    }

    public function contents(): string
    {
        $path = $this->path();

        return $path !== '' && is_file($path) ? (string) file_get_contents($path) : '';
    }

    public function move(string $target): static
    {
        $path = $this->path();

        $moved = $path !== '' && is_file($path)
            ? rename($path, $target)
            : false;

        if (!$moved) {
            throw new \RuntimeException(sprintf('Unable to move upload [%s] to [%s].', $this->name(), $target));
        }

        return $this;
    }
}