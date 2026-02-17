<?php

declare(strict_types=1);

namespace MaskologLoggerTests\Monolog\Functional\Source;

use Psr\Http\Message\MessageInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;
use InvalidArgumentException;

class TestPsr7Response implements ResponseInterface
{
    /**
     * @var int
     */
    private $statusCode;

    /**
     * @var string
     */
    private $reasonPhrase;

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

    /** @var array<int, string> */
    private const REASON_PHRASES = [
        100 => 'Continue',
        101 => 'Switching Protocols',
        102 => 'Processing',
        200 => 'OK',
        201 => 'Created',
        202 => 'Accepted',
        203 => 'Non-Authoritative Information',
        204 => 'No Content',
        205 => 'Reset Content',
        206 => 'Partial Content',
        207 => 'Multi-Status',
        208 => 'Already Reported',
        226 => 'IM Used',
        300 => 'Multiple Choices',
        301 => 'Moved Permanently',
        302 => 'Found',
        303 => 'See Other',
        304 => 'Not Modified',
        305 => 'Use Proxy',
        307 => 'Temporary Redirect',
        308 => 'Permanent Redirect',
        400 => 'Bad Request',
        401 => 'Unauthorized',
        402 => 'Payment Required',
        403 => 'Forbidden',
        404 => 'Not Found',
        405 => 'Method Not Allowed',
        406 => 'Not Acceptable',
        407 => 'Proxy Authentication Required',
        408 => 'Request Timeout',
        409 => 'Conflict',
        410 => 'Gone',
        411 => 'Length Required',
        412 => 'Precondition Failed',
        413 => 'Payload Too Large',
        414 => 'URI Too Long',
        415 => 'Unsupported Media Type',
        416 => 'Range Not Satisfiable',
        417 => 'Expectation Failed',
        418 => 'I\'m a teapot',
        421 => 'Misdirected Request',
        422 => 'Unprocessable Entity',
        423 => 'Locked',
        424 => 'Failed Dependency',
        425 => 'Too Early',
        426 => 'Upgrade Required',
        428 => 'Precondition Required',
        429 => 'Too Many Requests',
        431 => 'Request Header Fields Too Large',
        451 => 'Unavailable For Legal Reasons',
        500 => 'Internal Server Error',
        501 => 'Not Implemented',
        502 => 'Bad Gateway',
        503 => 'Service Unavailable',
        504 => 'Gateway Timeout',
        505 => 'HTTP Version Not Supported',
        506 => 'Variant Also Negotiates',
        507 => 'Insufficient Storage',
        508 => 'Loop Detected',
        510 => 'Not Extended',
        511 => 'Network Authentication Required',
    ];

    /**
     * @param int $statusCode
     * @param array $headers
     * @param StreamInterface|string|null $body
     * @param string $protocolVersion
     * @param string $reasonPhrase
     */
    public function __construct(
        int $statusCode = 200,
        array $headers = [],
        $body = null,
        string $protocolVersion = '1.1',
        string $reasonPhrase = ''
    ) {
        $this->statusCode = $this->validateStatusCode($statusCode);
        $this->headers = $this->normalizeHeaders($headers);
        $this->body = $body instanceof StreamInterface ? $body : new TestStreamPsr($body ?? '');
        $this->protocolVersion = $protocolVersion;
        $this->reasonPhrase = $reasonPhrase !== '' ? $reasonPhrase : (self::REASON_PHRASES[$statusCode] ?? '');
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

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public function withStatus(int $code, string $reasonPhrase = ''): ResponseInterface
    {
        $code = $this->validateStatusCode($code);

        if ($reasonPhrase === '') {
            $reasonPhrase = self::REASON_PHRASES[$code] ?? '';
        }

        if ($code === $this->statusCode && $reasonPhrase === $this->reasonPhrase) {
            return $this;
        }

        $new = clone $this;
        $new->statusCode = $code;
        $new->reasonPhrase = $reasonPhrase;

        return $new;
    }

    public function getReasonPhrase(): string
    {
        return $this->reasonPhrase;
    }

    private function validateStatusCode(int $code): int
    {
        if ($code < 100 || $code > 599) {
            throw new InvalidArgumentException('Status code must be between 100 and 599');
        }

        return $code;
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
            $value = [$value];    }

        $normalized = [];
        foreach ($value as $item) {
            if (!is_string($item) && !is_numeric($item)) {
                throw new InvalidArgumentException('Header value must be a string or array of strings');
            }
            $normalized[] = trim((string) $item, " \t");
        }

        return $normalized;
    }

    /**
     * Get all response data as an array
     */
    public function toArray(): array
    {
        return [
            'status_code' => $this->statusCode,
            'reason_phrase' => $this->reasonPhrase,
            'headers' => $this->headers,
            'body' => (string) $this->body,
            'protocol_version' => $this->protocolVersion,
        ];
    }

    /**
     * Create a response from an array of data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            $data['status_code'] ?? 200,
            $data['headers'] ?? [],
            $data['body'] ?? '',
            $data['protocol_version'] ?? '1.1',
            $data['reason_phrase'] ?? ''
        );
    }
}



