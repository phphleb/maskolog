<?php

declare(strict_types=1);

namespace Maskolog\Processors\Converters;

use Maskolog\Extensions\Psr7StreamTrait;
use Maskolog\Internal\Exceptions\InvalidArgumentException;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Separate array conversion for PSR-7 Request.
 *
 * @see AbstractManagedLoggerFactory::pushUpdateObjectProcessor()
 */
class Psr7RequestProcessor extends AbstractObjectProcessor
{
    use Psr7StreamTrait;

    /**
     * @var int
     */
    protected $maxBodySize;

    /**
     * @var int
     */
    protected $chunkSize;

    public function __construct(
        int $maxBodySize = 1048576,
        int $chunkSize = 8192
    )
    {
        if ($maxBodySize <= 0) {
            throw new InvalidArgumentException('The response body size value cannot be less than or equal to 0');
        }
        if ($chunkSize <= 0) {
            throw new InvalidArgumentException('The chunk size value cannot be less than or equal to 0');
        }
        $this->maxBodySize = $maxBodySize;
        $this->chunkSize = $chunkSize;
    }

    /**
     * @inheritDoc
     */
    public function updateObject(object $object)
    {
        if (!$object instanceof RequestInterface) {
            return $object;
        }
        $request = $object;

        /** @var array<string, int|null|string|array<string, int|null|string|array<mixed>>> $result */
        $result = [
            'method' => $request->getMethod(),
            'uri' => (string)$request->getUri(),
        ];
        $result['protocol_version'] = $request->getProtocolVersion();

        $headers = [];
        foreach ($request->getHeaders() as $name => $values) {
            // For convenience of further masking, it is converted to lower case uniformly.
            $headers[strtolower($name)] = $values;
        }
        $result['headers'] = $headers;
        if (isset($headers['cookie'])) {
            $result['headers']['cookies'] = $this->parseCookies($headers['cookie']);
        }
        $body = $request->getBody();
        $result['body'] = $this->decodeJsonBody(
            $this->readBodySafely($body, $this->maxBodySize, $this->chunkSize),
            $this->isJson($request),
        );

        if ($request instanceof ServerRequestInterface) {
            $result['attributes'] = $this->getAttributes($request);
            $result['server_params'] = $this->getServerParams($request);
            $result['uploaded_files'] = $this->getUploadedFiles($request);
        }

        return [get_class($object) => $result];
    }

    /**
     * Allows you to change how Cookies are parsed during inheritance.
     *
     * @param mixed $cookies
     * @return mixed
     */
    protected function parseCookies($cookies)
    {
        return $this->parseCookieHeaders($cookies);
    }

    /**
     * Allows you to change the formation of parameters when inheriting a method.
     *
     * @return array<mixed>
     */
    protected function getServerParams(ServerRequestInterface $request): array
    {
        return $request->getServerParams();
    }

    /**
     * Allows you to change the formation of attributes when inheriting a method.
     *
     * @return array<mixed>
     */
    protected function getAttributes(ServerRequestInterface $request): array
    {
        return $request->getAttributes();
    }

    /**
     * Allows you to change the formation of uploaded files when inheriting a method.
     *
     * @return array<mixed>
     */
    protected function getUploadedFiles(ServerRequestInterface $request): array
    {
        return $request->getUploadedFiles();
    }

    protected function isJson(RequestInterface $request): bool
    {
        $contentType = $request->getHeaderLine('Content-Type');

        return stripos($contentType, 'application/json') !== false;
    }
}