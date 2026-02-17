<?php

declare(strict_types=1);

namespace MaskologLoggerTests\Monolog\Functional\Source;

use Psr\Http\Message\UriInterface;
use InvalidArgumentException;

class TestUriFromRequest implements UriInterface
{
    /**
     * @var string
     */
    private $scheme;

    /**
     * @var string
     */
    private $userInfo;

    /**
     * @var string
     */
    private $host;

    /**
     * @var int|null
     */
    private $port;

    /**
     * @var string
     */
    private $path;

    /**
     * @var string
     */
    private $query;

    /**
     * @var string
     */
    private $fragment;

    public function __construct(
        string $scheme = 'http',
        string $host = 'localhost',
        ?int $port = null,
        string $path = '/',
        string $query = '',
        string $fragment = '',
        string $user = '',
        string $password = ''
    ) {
        $this->scheme = $this->normalizeScheme($scheme);
        $this->host = strtolower($host);
        $this->port = $this->normalizePort($port);
        $this->path = $this->normalizePath($path);
        $this->query = $query;
        $this->fragment = $fragment;
        $this->userInfo = $this->formatUserInfo($user, $password);
    }

    public function getScheme(): string
    {
        return $this->scheme;
    }

    public function getAuthority(): string
    {
        $authority = $this->host;

        if ($this->userInfo !== '') {
            $authority = $this->userInfo . '@' . $authority;
        }

        if ($this->port !== null && !$this->isStandardPort()) {
            $authority .= ':' . $this->port;
        }

        return $authority;
    }

    public function getUserInfo(): string
    {
        return $this->userInfo;
    }

    public function getHost(): string
    {
        return $this->host;
    }

    public function getPort(): ?int
    {
        return $this->port;
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function getQuery(): string
    {
        return $this->query;
    }

    public function getFragment(): string
    {
        return $this->fragment;
    }

    public function withScheme(string $scheme): UriInterface
    {
        $scheme = $this->normalizeScheme($scheme);

        if ($scheme === $this->scheme) {
            return $this;
        }

        $new = clone $this;
        $new->scheme = $scheme;
        $new->port = $this->normalizePort($new->port);

        return $new;
    }

    public function withUserInfo(string $user, ?string $password = null): UriInterface
    {
        $userInfo = $this->formatUserInfo($user, $password);

        if ($userInfo === $this->userInfo) {
            return $this;
        }

        $new = clone $this;
        $new->userInfo = $userInfo;

        return $new;
    }

    public function withHost(string $host): UriInterface
    {
        $host = strtolower($host);

        if ($host === $this->host) {
            return $this;
        }

        $new = clone $this;
        $new->host = $host;

        return $new;
    }

    public function withPort(?int $port): UriInterface
    {
        $port = $this->normalizePort($port);

        if ($port === $this->port) {
            return $this;
        }

        $new = clone $this;
        $new->port = $port;

        return $new;
    }

    public function withPath(string $path): UriInterface
    {
        $path = $this->normalizePath($path);

        if ($path === $this->path) {
            return $this;
        }

        $new = clone $this;
        $new->path = $path;

        return $new;
    }

    public function withQuery(string $query): UriInterface
    {
        if ($query === $this->query) {
            return $this;
        }

        $new = clone $this;
        $new->query = $query;

        return $new;
    }

    public function withFragment(string $fragment): UriInterface
    {
        if ($fragment === $this->fragment) {
            return $this;
        }

        $new = clone $this;
        $new->fragment = $fragment;

        return $new;
    }

    public function __toString(): string
    {
        $uri = '';

        if ($this->scheme !== '') {
            $uri .= $this->scheme . ':';
        }

        $authority = $this->getAuthority();
        if ($authority !== '') {
            $uri .= '//' . $authority;
        }

        $path = $this->path;
        if ($path !== '') {
            if ($authority !== '' && $path[0] !== '/') {
                $path = '/' . $path;
            } elseif ($authority === '' && str_starts_with($path, '//')) {
                $path = '/' . ltrim($path, '/');
            }
        }
        $uri .= $path;

        if ($this->query !== '') {
            $uri .= '?' . $this->query;
        }

        if ($this->fragment !== '') {
            $uri .= '#' . $this->fragment;
        }

        return $uri;
    }

    private function normalizeScheme(string $scheme): string
    {
        $scheme = strtolower(trim($scheme));

        if (!in_array($scheme, ['http', 'https', 'ftp', 'ftps', ''], true)) {
            throw new InvalidArgumentException('Unsupported scheme: ' . $scheme);
        }

        return $scheme;
    }

    private function normalizePort(?int $port): ?int
    {
        if ($port === null) {
            return null;
        }

        if ($port < 1 || $port > 65535) {
            throw new InvalidArgumentException('Port must be between 1 and 65535');
        }

        if ($this->isStandardPort($port)) {
            return null;
        }

        return $port;
    }


    private function normalizePath(string $path): string
    {
        return (string)preg_replace_callback(
            '/[^a-zA-Z0-9_\-\.~!$&\'()*+,;=:@\/]/',
            function ($matches) {
                return rawurlencode($matches[0]);
            },
            $path
        );
    }


    private function formatUserInfo(string $user, ?string $password = null): string
    {
        if ($user === '') {
            return '';
        }

        $userInfo = $user;
        if ($password !== null && $password !== '') {
            $userInfo .= ':' . $password;
        }

        return $userInfo;
    }

    private function isStandardPort(?int $port = null): bool
    {
        $port = $port ?? $this->port;

        return ($this->scheme === 'http' && $port === 80) ||
            ($this->scheme === 'https' && $port === 443) ||
            ($this->scheme === 'ftp' && $port === 21) ||
            ($this->scheme === 'ftps' && $port === 990);
    }
}

