<?php

namespace MaskologLoggerTests\Monolog\Functional;

use Maskolog\Logger;
use MaskologLoggerTests\Monolog\Functional\LoggerFactory\ObjectProcessorTestLoggerFactory;
use MaskologLoggerTests\Monolog\Functional\Source\TestPsr7Request;
use MaskologLoggerTests\Monolog\Functional\Source\TestPsr7Response;
use Monolog\Formatter\JsonFormatter;
use Monolog\Handler\TestHandler;
use PHPUnit\Framework\TestCase;

class ObjectProcessorTest extends TestCase
{
    use TestHandlerConverterTrait;

    public function testRequestProcessor()
    {
        $factory = new ObjectProcessorTestLoggerFactory();
        $logger = new Logger($factory);
        $testHandler = new TestHandler();
        $testHandler->setFormatter(new JsonFormatter(includeStacktraces: true));
        $logger = $logger->withHandler($testHandler);
        $body = __METHOD__;
        $request = new TestPsr7Request(body: $body);
        $logger->info('Test request', ['request' => $request]);
        $result = $this->convertHandler($testHandler);

        $this->assertCount(1, $result);
        $obj = ((array)current($result)?->context->request)[TestPsr7Request::class];

        $this->assertSame($body, $obj->body->content);
    }

    public function testResponseProcessor()
    {
        $factory = new ObjectProcessorTestLoggerFactory();
        $logger = new Logger($factory);
        $testHandler = new TestHandler();
        $testHandler->setFormatter(new JsonFormatter(includeStacktraces: true));
        $logger = $logger->withHandler($testHandler);
        $body = __METHOD__;
        $response = new TestPsr7Response(body: $body);
        $logger->info('Test request', ['request' => $response]);
        $result = $this->convertHandler($testHandler);

        $this->assertCount(1, $result);
        $obj = ((array)current($result)?->context->request)[TestPsr7Response::class];

        $this->assertSame($body, $obj->body->content);
    }
}