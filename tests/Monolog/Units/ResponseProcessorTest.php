<?php

declare(strict_types=1);

namespace MaskologLoggerTests\Monolog\Units;

use Maskolog\Processors\Converters\Psr7ResponseProcessor;
use MaskologLoggerTests\Monolog\Functional\Source\TestObject;
use MaskologLoggerTests\Monolog\Functional\Source\TestPsr7Response;
use PHPUnit\Framework\TestCase;

class ResponseProcessorTest extends TestCase
{
   public function testNotResponseInit(): void
   {
       $response = new Psr7ResponseProcessor();
       $object = new TestObject();
       $result = $response->updateObject($object);

       $this->assertIsObject($result);
       $this->assertTrue($object === $result);
   }

    public function testUpdateNotRequestInit(): void
    {
        $response = new Psr7ResponseProcessor();
        $context = ['obj' => new TestObject()];
        $result = $response->update($context);

        $this->assertIsArray($result);
        $this->assertEquals($context, $result);
    }

    public function testResponseObjectConvert(): void
    {
        $processor = new Psr7ResponseProcessor();
        $context = ['obj' => new TestPsr7Response()];
        $result = $processor->update($context);

        $this->assertIsArray($result);
        $this->assertIsArray($result['obj']);
        $this->assertIsArray($result['obj'][TestPsr7Response::class]);
    }

    public function testResponseObjectData(): void
    {
        $processor = new Psr7ResponseProcessor();
        $body = str_repeat('-', 10);
        $headers = ['X-Header' => 'example'];
        $context = ['obj' => new TestPsr7Response(statusCode: 404, headers: $headers, body: $body)];
        $result = $processor->update($context);

        $this->assertIsArray($result);
        $this->assertIsArray($result['obj']);
        $this->assertIsArray($result['obj'][TestPsr7Response::class]);
        $subObj = $result['obj'][TestPsr7Response::class];
        $this->assertEquals(404, $subObj['status_code']);
        $this->assertEquals(['x-header' => ['example']], $subObj['headers']);
        $this->assertEquals($body, $subObj['body']['content']);
    }

    public function testResponseJsonConvert(): void
    {
        $processor = new Psr7ResponseProcessor();
        $headers = ['Content-Type' => 'application/json'];
        $response = new TestPsr7Response(headers: $headers, body: '{"jsonKey":"jsonContent"}');
        $context = ['obj' => $response];
        $result = $processor->update($context);
        $actual = ['jsonKey' => 'jsonContent'];

        $this->assertIsArray($result);
        $this->assertIsArray($result['obj']);
        $this->assertIsArray($result['obj'][TestPsr7Response::class]);
        $this->assertEquals($actual, $result['obj'][TestPsr7Response::class]['body']['content']);
    }

    public function testResponseOversizeConvert(): void
    {
        $processor = new Psr7ResponseProcessor(10, 5);
        $body = str_repeat('-', 15);
        $context = ['obj' => new TestPsr7Response(body: $body)];
        $result = $processor->update($context);

        $this->assertIsArray($result);
        $this->assertIsArray($result['obj']);
        $this->assertEquals(null, $result['obj'][TestPsr7Response::class]['body']['content']);
    }
}