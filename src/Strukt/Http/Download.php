<?php

namespace Strukt\Http;

use Strukt\Contract\Http\DownloadInterface;

/**
 * File offered to the client as an attachment or an inline payload.
 *
 * The payload is read lazily from its backing path only when the content is
 * requested, so a large file is never copied into memory just to build the
 * response.
 */
final class Download implements DownloadInterface
{
    /**
     * @var array<string, string> Media types keyed by file extension.
     */
    private const MIME = [
        'pdf' => 'application/pdf',
        'txt' => 'text/plain',
        'json' => 'application/json',
        'csv' => 'text/csv',
        'xml' => 'application/xml',
        'zip' => 'application/zip',
        'gz' => 'application/gzip',
        'png' => 'image/png',
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'gif' => 'image/gif',
        'svg' => 'image/svg+xml',
        'webp' => 'image/webp',
        'html' => 'text/html',
        'css' => 'text/css',
        'js' => 'text/javascript',
    ];

    /**
     * Content-disposition mode, either `attachment` or `inline`.
     */
    private string $disposition = 'attachment';

    /**
     * @param string $path Backing file path.
     * @param string|null $filename Filename presented to the client.
     * @param string|null $mime Media type, or null to detect it.
     */
    public function __construct(
        private string $path,
        private ?string $filename = null,
        private ?string $mime = null,
    ) {
    }

    public function filename(): string
    {
        return $this->filename ?? basename($this->path);
    }

    public function setFilename(string $filename): static
    {
        if ($filename === '') {
            throw new \InvalidArgumentException('Filename cannot be empty.');
        }

        $this->filename = $filename;

        return $this;
    }

    public function mime(): string
    {
        if ($this->mime !== null) {
            return $this->mime;
        }

        $extension = strtolower(pathinfo($this->filename(), PATHINFO_EXTENSION));

        return self::MIME[$extension] ?? 'application/octet-stream';
    }

    public function setMime(string $mime): static
    {
        if ($mime === '') {
            throw new \InvalidArgumentException('Media type cannot be empty.');
        }

        $this->mime = $mime;

        return $this;
    }

    public function size(): int
    {
        return is_file($this->path) ? (int) filesize($this->path) : 0;
    }

    public function content(): string
    {
        return is_file($this->path) ? (string) file_get_contents($this->path) : '';
    }

    public function disposition(): string
    {
        return $this->disposition;
    }

    public function inline(): static
    {
        $this->disposition = 'inline';

        return $this;
    }

    public function attachment(): static
    {
        $this->disposition = 'attachment';

        return $this;
    }

    public function toHeaderValue(): string
    {
        return sprintf('%s; filename="%s"', $this->disposition, $this->filename());
    }

    public function send(): void
    {
        if (!headers_sent()) {
            header('Content-Type: ' . $this->mime());
            header('Content-Length: ' . $this->size());
            header('Content-Disposition: ' . $this->toHeaderValue());
        }

        echo $this->content();
    }
}