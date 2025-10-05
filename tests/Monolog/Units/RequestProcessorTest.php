<?php

declare(strict_types=1);

namespace MaskologLoggerTests\Monolog\Units;

use Maskolog\Processors\Converters\Psr7RequestProcessor;
use MaskologLoggerTests\Monolog\Functional\Source\TestObject;
use MaskologLoggerTests\Monolog\Functional\Source\TestPsr7Request;
use PHPUnit\Framework\TestCase;

class RequestProcessorTest extends TestCase
{
   public function testNotRequestInit(): void
   {
       $processor = new Psr7RequestProcessor();
       $object = new TestObject();
       $result = $processor->updateObject($object);

       $this->assertIsObject($result);
       $this->assertTrue($object === $result);
   }

    public function testUpdateNotRequestInit(): void
    {
        $processor = new Psr7RequestProcessor();
        $context = ['obj' => new TestObject()];
        $result = $processor->update($context);

        $this->assertIsArray($result);
        $this->assertEquals($context, $result);
    }

    public function testRequestObjectConvert(): void
    {
        $processor = new Psr7RequestProcessor();
        $context = ['obj' => new TestPsr7Request()];
        $result = $processor->update($context);

        $this->assertIsArray($result);
        $this->assertIsArray($result['obj']);
        $this->assertIsArray($result['obj'][TestPsr7Request::class]);
    }

    public function testRequestObjectData(): void
    {
        $processor = new Psr7RequestProcessor(10, 4);
        $body = str_repeat('-', 10);
        $headers = ['X-Header' => 'example'];
        $context = ['obj' => new TestPsr7Request(method: 'PUT', headers: $headers, body: $body)];
        $result = $processor->update($context);

        $this->assertIsArray($result);
        $this->assertIsArray($result['obj']);
        $this->assertIsArray($result['obj'][TestPsr7Request::class]);
        $subObj = $result['obj'][TestPsr7Request::class];
        $this->assertEquals($body, $subObj['body']['content']);
        $this->assertEquals('PUT', $subObj['method']);
        $this->assertEquals(['x-header' => ['example']], $subObj['headers']);
    }

    public function testRequestJsonConvert(): void
    {
        $processor = new Psr7RequestProcessor();
        $headers = ['Content-Type' => 'application/json'];
        $request = new TestPsr7Request(headers: $headers, body: '{"jsonKey":"jsonContent"}');
        $context = ['obj' => $request];
        $result = $processor->update($context);
        $actual = ['jsonKey' => 'jsonContent'];

        $this->assertIsArray($result);
        $this->assertIsArray($result['obj']);
        $this->assertIsArray($result['obj'][TestPsr7Request::class]);
        $this->assertEquals($actual, $result['obj'][TestPsr7Request::class]['body']['content']);
    }

    public function testRequestOversizeConvert(): void
    {
        $processor = new Psr7RequestProcessor(10, 5);
        $body = str_repeat('-', 15);
        $context = ['obj' => new TestPsr7Request(body: $body)];
        $result = $processor->update($context);

        $this->assertIsArray($result);
        $this->assertIsArray($result['obj']);
        $this->assertEquals(null, $result['obj'][TestPsr7Request::class]['body']['content']);
    }
}