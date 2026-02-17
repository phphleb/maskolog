<?php

declare(strict_types=1);

namespace MaskologLoggerTests\Monolog\Functional\Source;

use Psr\Http\Message\MessageInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UriInterface;
use InvalidArgumentException;

class TestPsr7Request implements RequestInterface
{
    /**
     * @var UriInterface|TestUriFromRequest
     */
    private $uri;

    /**
     * @var string
     */
    private $method;

    /**
     * @var string
     */
    private $requestTarget;

    /**
     * @var StreamInterface|TestStreamPsr
     */
    private $body;

    /**
     * @var array
     */
    private $headers;

    /**
     * @var string
     */
    private $protocolVersion;

    /**
     * @param string $method
     * @param UriInterface|string|null $uri
     * @param array $headers
     * @param StreamInterface|string|null $body
     * @param string $protocolVersion
     */
    public function __construct(
        string $method = 'GET',
        $uri = null,
        array $headers = [],
        $body = null,
        string $protocolVersion = '1.1'
    ) {
        $this->method = $this->validateMethod($method);
        $this->uri = $uri instanceof UriInterface ? $uri : new TestUriFromRequest();
        $this->headers = $this->normalizeHeaders($headers);
        $this->body = $body instanceof StreamInterface ? $body : new TestStreamPsr($body ?? '');
        $this->protocolVersion = $protocolVersion;
        $this->requestTarget = '';
    }

    public function getProtocolVersion(): string
    {
        return $this->protocolVersion;
    }

    public function withProtocolVersion(string $version): MessageInterface
    {
        if ($version === $this->protocolVersion) {
            return $this;
        }

        $new = clone $this;
        $new->protocolVersion = $version;

        return $new;
    }

    public function getHeaders(): array
    {
        return $this->headers;
    }

    public function hasHeader(string $name): bool
    {
        return isset($this->headers[strtolower($name)]);
    }

    public function getHeader(string $name): array
    {
        $name = strtolower($name);
        return $this->headers[$name] ?? [];
    }

    public function getHeaderLine(string $name): string
    {
        $values = $this->getHeader($name);
        return implode(', ', $values);
    }

    public function withHeader(string $name, $value): MessageInterface
    {
        $normalized = $this->normalizeHeaderValue($value);
        $normalizedName = strtolower($name);

        if (isset($this->headers[$normalizedName]) && $this->headers[$normalizedName] === $normalized) {
            return $this;
        }

        $new = clone $this;
        $new->headers[$normalizedName] = $normalized;

        return $new;
    }

    public function withAddedHeader(string $name, $value): MessageInterface
    {
        $normalized = $this->normalizeHeaderValue($value);
        $normalizedName = strtolower($name);

        $new = clone $this;
        if (isset($new->headers[$normalizedName])) {
            $new->headers[$normalizedName] = array_merge($new->headers[$normalizedName], $normalized);
        } else {
            $new->headers[$normalizedName] = $normalized;
        }

        return $new;
    }

    public function withoutHeader(string $name): MessageInterface
    {
        $normalizedName = strtolower($name);

        if (!isset($this->headers[$normalizedName])) {
            return $this;
        }

        $new = clone $this;
        unset($new->headers[$normalizedName]);

        return $new;
    }

    public function getBody(): StreamInterface
    {
        return $this->body;
    }

    public function withBody(StreamInterface $body): MessageInterface
    {
        if ($body === $this->body) {
            return $this;
        }

        $new = clone $this;
        $new->body = $body;

        return $new;
    }

    public function getRequestTarget(): string
    {
        if ($this->requestTarget !== '') {
        return $this->requestTarget;
    }

        $target = $this->uri->getPath();
        if ($target === '') {
            $target = '/';
        }

        $query = $this->uri->getQuery();
        if ($query !== '') {
            $target .= '?' . $query;
        }

        return $target;
    }

    public function withRequestTarget(string $requestTarget): RequestInterface
    {
        if ($requestTarget === $this->requestTarget) {
            return $this;
        }

        if (preg_match('/\s/', $requestTarget)) {
            throw new InvalidArgumentException('Request target cannot contain whitespace');
        }

        $new = clone $this;
        $new->requestTarget = $requestTarget;

        return $new;
    }

    public function getMethod(): string
    {
        return $this->method;
    }

    public function withMethod(string $method): RequestInterface
    {
        $method = $this->validateMethod($method);

        if ($method === $this->method) {
            return $this;
        }

        $new = clone $this;
        $new->method = $method;

        return $new;
    }

    public function getUri(): UriInterface
    {
        return $this->uri;
    }

    public function withUri(UriInterface $uri, bool $preserveHost = false): RequestInterface
    {
        if ($uri === $this->uri) {
            return $this;
        }

        $new = clone $this;
        $new->uri = $uri;

        if (!$preserveHost || !$this->hasHeader('host')) {
            $host = $uri->getHost();
            if ($host !== '') {
                $port = $uri->getPort();
                if ($port !== null) {
                    $host .= ':' . $port;
                }
                $new = $new->withHeader('Host', $host);
            }
        }

        return $new;
    }

    private function validateMethod(string $method): string
    {
        if (!preg_match('/^[A-Z]+$/', $method)) {
            throw new InvalidArgumentException('Invalid HTTP method');
        }

        return $method;
    }

    private function normalizeHeaders(array $headers): array
    {
        $normalized = [];

        foreach ($headers as $name => $value) {
            $normalizedName = strtolower($name);
            $normalized[$normalizedName] = $this->normalizeHeaderValue($value);
        }

        return $normalized;
    }

    private function normalizeHeaderValue($value): array
    {
        if (!is_array($value)) {
            $value = [$value];
        }

        $normalized = [];
        foreach ($value as $item) {
            if (!is_string($item) && !is_numeric($item)) {
                throw new InvalidArgumentException('Header value must be a string or array of strings');
            }
            $normalized[] = trim((string) $item, " \t");
        }

        return $normalized;
    }
}

