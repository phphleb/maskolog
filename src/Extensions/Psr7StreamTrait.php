<?php

declare(strict_types=1);

namespace Maskolog\Extensions;

use Psr\Http\Message\StreamInterface;

trait Psr7StreamTrait
{
    /**
     * @return array<string, int|string|null>
     */
    protected function getStreamFormat(
        StreamInterface $stream,
        ?string $content = null,
        ?int $position = null,
        ?string $error = null,
    ): array {
        $result = [
            'class' => get_class($stream),
            'id' => spl_object_id($stream),
            'size' => $stream->getSize(),
            'position' => $position,
            'content' => $content,
        ];
        if ($error) {
            $result['error'] = $error;
        }
        return $result;
    }

    /**
     * Reads the request body if it is less than the set limit.
     * You cannot return part of the body, as it is supposed to be masked later.
     * Also, here the original request object is left without changes to the rewind position.
     *
     * @return null|array<string, int|string|null>
     */
    protected function readBodySafely(StreamInterface $stream, int $maxSize, int $chunkSize = 8192): array|null
    {
        if (!$stream->isReadable()) {
            return null;
        }
        $pos = null;
        if ($stream->isSeekable()) {
            try { $pos = $stream->tell();} catch (\Throwable $e) { $pos = null; }
        }

        if ($stream->getSize() > $maxSize) {
            $error = "Exceeding the established size of {$maxSize} for the body. Origin " . $stream->getSize();

            return $this->getStreamFormat($stream, position: $pos, error: $error);
        }

        if ($stream->isSeekable()) {
            try { $stream->rewind(); } catch (\Throwable $e) { $pos = null; }
        }

        $need = $maxSize + 1;
        $buf = '';
        $chunk = min(8192, $need);

        while ($need > 0 && !$stream->eof()) {
            $part = $stream->read($chunk);
            if ($part === '') break;
            $buf .= $part;
            $need -= strlen($part);
            if (strlen($buf) > $maxSize) {
                if ($pos !== null && $stream->isSeekable()) {
                    try { $stream->seek($pos); } catch (\Throwable $e) {}
                }
                $error = "Exceeding the established size of {$maxSize} for the body";

                return $this->getStreamFormat($stream, position: $pos, error: $error);
            }
            $chunk = min($chunkSize, $need);
        }

        if ($pos !== null && $stream->isSeekable()) {
            try { $stream->seek($pos); } catch (\Throwable $e) {}
        }

        return $this->getStreamFormat($stream, $buf, $pos);
    }

    /**
     * @param string $header
     * @return array<string, string>
     */
    protected function parseStringCookieHeader(string $header): array
    {
        $pairs = array_filter(array_map('trim', explode(';', $header)));
        $cookies = [];
        foreach ($pairs as $pair) {
            [$k, $v] = array_map('trim', explode('=', $pair, 2) + [1 => '']);
            $cookies[$k] = $v;
        }
        return $cookies;
    }

    protected function parseCookieHeaders(mixed $header): mixed
    {
        if (is_string($header)) {
            return $this->parseStringCookieHeader($header);
        }
        if (is_array($header)) {
            return $this->parseArrayCookieHeader($header);
        }
        return $header;
    }

    /**
     * @param array<mixed> $header
     * @return array<mixed>
     */
    protected function parseArrayCookieHeader(array $header): array
    {
        $result = [];
        foreach ($header as $key => $value) {
            if (is_array($value)) {
                $result[$key] = $value;
            } elseif (is_string($value)) {
                if (str_contains($value, ';')) {
                    $items = array_map('trim', explode(';', $value));
                    $hasEqualsInItems = 0;
                    foreach ($items as $item) {
                        if (str_contains($item, '=')) {
                            $hasEqualsInItems ++;
                        }
                    }

                    if ($hasEqualsInItems !== count($items)) {
                        $result[$key] = trim($value);
                    } else {
                        $items = array_filter($items, fn($v) => $v !== '');
                        $result[$key] = $items;
                    }
                } else {
                    $result[$key] = $value;
                }
            }
        }
        return $result;
    }

    /**
     * @param array<string, int|string|null>|null $body
     * @param bool $isJson
     * @return array<string, int|string|null>|null
     */
    protected function decodeJsonBody(?array $body, bool $isJson): ?array
    {
        if (!$body) {
            return is_array($body) ? [] : null;
        }
        if (array_key_exists( 'body', $body)) {
            $body['content'] = $body['body'];
            unset($body['body']);
        }
        if (isset($body['content'])
            && is_string($body['content'])
            && $isJson
        ) {
            $decoded = json_decode($body['content'], true);
            $body['content'] = null;
            if (json_last_error() === JSON_ERROR_NONE) {
                $body['content'] = $decoded;
            } else {
                $body['json_error_code'] = json_last_error();
                $body['json_decode_error'] = json_last_error_msg();
            }
        }
        /** @var array<string, int|string|null> */
        return $body;
    }
}