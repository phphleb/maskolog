<?php

declare(strict_types=1);

namespace Maskolog\Processors\Converters;

use Maskolog\Extensions\Psr7StreamTrait;
use Maskolog\Internal\Exceptions\InvalidArgumentException;
use Psr\Http\Message\ResponseInterface;

/**
 * Separate array conversion for PSR-7 Response.
 *
 * @see AbstractManagedLoggerFactory::pushUpdateObjectProcessor()
 */
class Psr7ResponseProcessor extends AbstractObjectProcessor
{
    use Psr7StreamTrait;

    public function __construct(
        readonly protected int $maxBodySize = 1048576,
        readonly protected int $chunkSize = 8192,
    )
    {
        if ($this->maxBodySize <= 0) {
            throw new InvalidArgumentException('The request body size value cannot be less than or equal to 0');
        }
        if ($this->chunkSize <= 0) {
            throw new InvalidArgumentException('The chunk size value cannot be less than or equal to 0');
        }
    }

    /**
     * @inheritDoc
     */
    public function updateObject(object $object): array|object
    {
        if ($object instanceof ResponseInterface) {
            $response = $object;

            $result = [];
            $body = $response->getBody();
            $result['protocol_version'] = $response->getProtocolVersion();
            $result['status_code'] = $response->getStatusCode();
            $result['reason_phrase'] = $response->getReasonPhrase();

            $headers = [];
            foreach ($response->getHeaders() as $name => $values) {
                // For convenience of further masking, it is converted to lower case uniformly.
                $headers[strtolower($name)] = $values;
            }
            $result['headers'] = $headers;
            if (isset($headers['cookie'])) {
                $result['headers']['cookies'] = $this->parseCookies($headers['cookie']);
            }
            $result['body'] = $this->decodeJsonBody(
                $this->readBodySafely($body, $this->maxBodySize, $this->chunkSize),
                $this->isJson($response)
            );

            return [$object::class => $result];
        }
        return $object;
    }

    /**
     * Allows you to change how Cookies are parsed during inheritance.
     */
    protected function parseCookies(mixed $cookies): mixed
    {
        return $this->parseCookieHeaders($cookies);
    }

    protected function isJson(ResponseInterface $response): bool
    {
        $contentType = $response->getHeaderLine('Content-Type');

        return stripos($contentType, 'application/json') !== false;
    }
}