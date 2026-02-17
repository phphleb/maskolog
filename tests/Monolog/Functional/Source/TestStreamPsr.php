<?php

declare(strict_types=1);

namespace MaskologLoggerTests\Monolog\Functional\Source;

use Psr\Http\Message\StreamInterface;
use RuntimeException;

class TestStreamPsr implements StreamInterface
{
    /**
     * @var string
     */
    private $body;

    /**
     * @var int
     */
    private $position = 0;

    /**
     * @var bool
     */
    private $closed = false;

    public function __construct(string $body = '')
    {
        $this->body = $body;
    }

    public function __toString(): string
    {
        if ($this->closed) {
            return '';
        }

        return $this->body;
    }

    public function close(): void
    {
        $this->closed = true;
        $this->position = 0;
    }

    public function detach()
    {
        $this->close();
        return null;
    }

    public function getSize(): ?int
    {
        if ($this->closed) {
            return null;
        }

        return strlen($this->body);
    }

    public function tell(): int
    {
        if ($this->closed) {
            throw new RuntimeException('Stream is closed');
        }

        return $this->position;
    }

    public function eof(): bool
    {
        return $this->closed || $this->position >= strlen($this->body);
    }

    public function isSeekable(): bool
    {
        return !$this->closed;
    }

    public function seek(int $offset, int $whence = SEEK_SET): void
    {
        if ($this->closed) {
            throw new RuntimeException('Stream is closed');
        }

        $newPosition = null;
        if ($whence === SEEK_SET) {
            $newPosition = $offset;
        }
        if ($whence === SEEK_CUR) {
            $newPosition = $this->position + $offset;
        }
        if ($whence === SEEK_END) {
            $newPosition = strlen($this->body) + $offset;
        }
        if ($newPosition === null) {
            throw new RuntimeException('Invalid whence value');
        }

        if ($newPosition < 0) {
            throw new RuntimeException('Seek position cannot be negative');
        }

        $this->position = $newPosition;
    }

    public function rewind(): void
    {
        $this->seek(0);
    }

    public function isWritable(): bool
    {
        return !$this->closed;
    }

    public function write(string $string): int
    {
        if ($this->closed) {
            throw new RuntimeException('Stream is closed');
        }

        $length = strlen($string);

        $this->body = substr($this->body, 0, $this->position) .
            $string .
            substr($this->body, $this->position + $length);

        $this->position += $length;

        return $length;
    }

    public function isReadable(): bool
    {
        return !$this->closed;
    }

    public function read(int $length): string
    {
        if ($this->closed) {
            throw new RuntimeException('Stream is closed');
        }

        if ($length < 0) {
            throw new RuntimeException('Length cannot be negative');
        }

        $data = substr($this->body, $this->position, $length);
        $this->position += strlen($data);

        return $data;
    }

    public function getContents(): string
    {
        if ($this->closed) {
            throw new RuntimeException('Stream is closed');
        }

        $contents = substr($this->body, $this->position);
        $this->position = strlen($this->body);

        return $contents;
    }

    public function getMetadata(?string $key = null)
    {
        $metadata = [
            'timed_out' => false,
            'blocked' => false,
            'eof' => $this->eof(),
            'unread_bytes' => 0,
            'stream_type' => 'test_stream',
            'wrapper_type' => 'test',
            'wrapper_data' => null,
            'mode' => 'r+',
            'seekable' => $this->isSeekable(),
            'uri' => 'test://stream',
        ];

        if ($key === null) {
            return $metadata;
        }

        return $metadata[$key] ?? null;
    }
}

